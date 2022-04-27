<?php

namespace PG\Notifications\Domain\Service;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use PG\Utility\Service\EmailService;

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
 * Service to aggregate backend changes by a specific user group and send notifications to a target email address
 */
class NotificationService
{
    /**
     * @var object|ObjectManager
     */
    protected $objectManager;

    /**
     * @var object|EmailService
     */
    protected $emailService;

    /**
     * @var array
     */
    protected $configuration = [];

    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->emailService = GeneralUtility::makeInstance(EmailService::class);
        /** @var ConfigurationManagerInterface $configurationManager */
        $configurationManager = $this->objectManager->get(ConfigurationManagerInterface::class);
        $this->configuration = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'Notifications');
    }


    /**
     * @param $userGroup
     * @param $recipient
     * @param string $timeRangeStart
     * @param string $timeRangeEnd
     * @param bool $notifyOnNoChanges
     * @return bool
     * @throws \Exception
     */
    public function sendChangeNotifications($userGroup, $recipient, $timeRangeStart = 'today', $timeRangeEnd = 'tomorrow -1 sec', $notifyOnNoChanges = false): bool
    {
        $groupData = BackendUtility::getRecord('be_groups', $userGroup);
        if (!$groupData || $groupData['uid'] != $userGroup) {
            return false;
        }

        try {
            $startDate = new \DateTime();
            $startDate->modify($timeRangeStart);
            $endDateModifier = substr($timeRangeEnd, 0, 1);
            // if we have a modifier as first character, the endtime is relative to whatever has been specified as start date
            if ($endDateModifier === '-' || $endDateModifier === '+') {
                $endDate = clone($startDate);
            // if no modifier, assume current time as "now"
            } else {
                $endDate = new \DateTime();
            }
            $endDate->modify($timeRangeEnd);
        } catch (\Exception $e) {
            DebugUtility::debug($e->getMessage());
            return false;
        }

        $historyService = GeneralUtility::makeInstance(ChangeHistoryService::class);
        $changes = $historyService->getHistoryForUserGroupForDay($userGroup, $startDate, $endDate);

        // no changes and no notification on empty changeset? Return early
        if (!count($changes) && !$notifyOnNoChanges) {
            return true;
        }

        // if we still should notify, prepare data and send notification
        $pids = [];
        foreach ($changes as $table => $changedItems) {
            $pids = array_merge($pids, array_keys($changedItems));
        }
        $pids = array_unique($pids);

        $settings = &$this->configuration['settings']['changelog'];
        #$this->emailService->setDebugMode(true);

        $recipients = GeneralUtility::trimExplode(',', $recipient);
        $recipient = array_shift($recipients);

        // switch backend language to german
        /** @var \TYPO3\CMS\Core\Localization\LanguageService $languageService */
        $languageService = $GLOBALS['LANG'];
        $languageService->init('de');
        #$languageService->lang = 'de';

        if ($this->emailService->sendTemplateEmail(
            $recipient,
            $settings['email']['sender'],
            LocalizationUtility::translate($settings['email']['subject'], 'Notifications') ?: $settings['email']['subject'],
            $settings['email']['template'],
            $this->configuration['view'],
            [
                'changes' => $changes,
                'users' => $this->getBackendUsers(),
                'pages' => $this->getPagesByIds($pids),
                'startDate' => $startDate,
                'endDate' => $endDate
            ],
            null,
            null,
            [],
            array_flip($recipients)
        )) {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    protected function getBackendUsers() : array
    {
        $queryBuilder = $this->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()->removeAll();
        $userResults = $queryBuilder->select('*')
            ->from('be_users')
            ->execute()
            ->fetchAll();
        $users = [];
        foreach ($userResults as $user) {
            unset($user['password']);
            $users[$user['uid']] = $user;
        }
        return $users;
    }

    /**
     * @param array $uids
     * @return array
     */
    protected function getPagesByIds(array $uids) : array
    {
        if (!count($uids)) {
            return [];
        }
        $queryBuilder = $this->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $pageResults = $queryBuilder->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in('uid', $uids)
            )->execute()
            ->fetchAll();
        $pages = [];
        foreach ($pageResults as $page) {
            $pages[$page['uid']] = $page;
        }
        return $pages;
    }

    /**
     * @param string $tableName
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    protected function getQueryBuilderForTable($tableName)
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
    }
}