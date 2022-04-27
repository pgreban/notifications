<?php

namespace PG\Notifications\Task;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use PG\Notifications\Domain\Service\NotificationService;

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
 * Scheduler task to execute notifications. We're extending the Extbase command task in order to be able to use it's TaskExecutor
 */
class NotificationTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * Function execute from the Scheduler
     *
     * @return bool TRUE on successful execution
     * @throws \Exception If an error occurs
     */
    public function execute() : bool
    {
        try {
            $notificationService = GeneralUtility::makeInstance(NotificationService::class);
            return $notificationService->sendChangeNotifications(
                $this->arguments['userGroup'],
                $this->arguments['recipient'],
                $this->arguments['timeRangeStart'],
                $this->arguments['timeRangeEnd'],
                $this->arguments['notifyOnNoChanges']
            );
        } catch (\Exception $e) {
            $this->logException($e);
            // Make sure the Scheduler gets exception details
            throw $e;
        }
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     */
    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }
}