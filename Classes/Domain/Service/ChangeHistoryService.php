<?php

namespace PG\Notifications\Domain\Service;

use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***
 *
 * This file is part of the "Notifications" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Philippe Greban <philippe.greban@gmail.com>
 *
 ***/

/**
 * Services fetches the change history by various criteria
 */
class ChangeHistoryService implements SingletonInterface
{

    /**
     * @var array
     */
    protected $tables = [
        'pages' => ['titleField' => 'title'],
        'tt_content' => ['titleField' => 'header']
    ];

    /**
     * @var object|RecordHistory
     */
    protected $historyService;

    public function __construct()
    {
        $this->historyService = GeneralUtility::makeInstance(RecordHistory::class);
    }

    /**
     * @param $userGroup
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    public function getHistoryForUserGroupForDay($userGroup, \DateTime $startDate, \DateTime $endDate): array
    {
        $history = [];
        foreach ($this->tables as $tablename => $tableConfiguration) {
            $tableHistory = $this->getHistoryForTable($tablename, $userGroup, $startDate, $endDate, $tableConfiguration);
            if ($tableHistory && count($tableHistory)) {
                $history[$tablename] = $tableHistory;
            }
        }
        return $history;
    }

    /**
     * Returns the change history for a given time frame grouped by pageId
     * @param string $tablename
     * @param int $userGroup
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param array $configuration
     */
    public function getHistoryForTable($tablename, $userGroup, \DateTime $startDate, \DateTime $endDate, array $configuration = [])
    {
        // Selecting the $this->maxSteps most recent states:
        $queryBuilder = $this->getQueryBuilderForTable('sys_history');
        $rows = $queryBuilder
            ->select('sys_history.*', 'sys_log.userid', 'sys_log.log_data', 'sys_log.recuid')
            ->from('sys_history')
            ->from('sys_log')
            ->leftJoin('sys_log', 'be_users', 'be_users',
                $queryBuilder->expr()->eq('sys_log.userid', $queryBuilder->quoteIdentifier('be_users.uid')))
            ->where(
                $queryBuilder->expr()->inSet(
                    'be_users.usergroup',
                    $userGroup,
                    false
                ),
                $queryBuilder->expr()->eq(
                    'sys_history.recuid',
                    $queryBuilder->quoteIdentifier('sys_log.recuid')
                ),
                $queryBuilder->expr()->eq(
                    'sys_history.tablename',
                    $queryBuilder->createNamedParameter($tablename, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->gte('sys_history.tstamp', $startDate->format('U')),
                    $queryBuilder->expr()->lte('sys_history.tstamp', $endDate->format('U'))
                )
            )
            ->orderBy('sys_history.recuid', 'ASC')
            ->addOrderBy('sys_log.uid', 'DESC')
            ->execute()
            ->fetchAll();

        $history = [];
        foreach ($rows as $item) {
            $itemHistory = $this->extractHistoryData($item);
            if (is_array($itemHistory)) {
                if (isset($configuration['titleField'])) {
                    $itemHistory['title'] = $itemHistory['data'][$configuration['titleField']];
                }

                if (!isset($history[$itemHistory['recpid']])) {
                    $history[$itemHistory['recpid']] = [];
                }
                if (!isset($history[$itemHistory['recpid']][$itemHistory['recuid']])) {
                    $history[$itemHistory['recpid']][$itemHistory['recuid']] = [];
                }
                $history[$itemHistory['recpid']][$itemHistory['recuid']][] = $itemHistory;
            }
        }
        return $history;
    }

    /**
     * @param string $tableName
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    protected function getQueryBuilderForTable($tableName)
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
    }

    /**
     * @param array $row
     * @return int|mixed
     */
    protected function extractHistoryData(array $row)
    {
        $historyData = json_decode($row['history_data'], true);
        $logData = json_decode($row['log_data'], true);
        if (is_array($historyData['newRecord']) && is_array($historyData['oldRecord'])) {
            // add current DB record of this item to the `data` array for later usage
            $currentRecord = BackendUtility::getRecord($row['tablename'], $row['recuid']);

            // Add information about the history to the changeLog
            $historyData['uid'] = $row['uid'];
            $historyData['tstamp'] = $row['tstamp'];
            $historyData['user'] = $row['userid'];
            $historyData['originalUser'] = (empty($logData['originalUser']) ? null : $logData['originalUser']);
            $historyData['tablename'] = $row['tablename'];
            $historyData['recuid'] = $row['recuid'];
            $historyData['recpid'] = $currentRecord['pid'];
            $historyData['data'] = $currentRecord;
        } else {
            debug('ERROR: [getHistoryData]');
            // error fallback
            return 0;
        }
        return $historyData;
    }
}