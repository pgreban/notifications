<?php

if (TYPO3_MODE === 'BE') {
    // register task
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\PG\Notifications\Task\NotificationTask::class] = [
        'extension' => 'notifications',
        'title' => 'Notifications',
        'description' => 'Sends notifications about content changes',
        'additionalFields' => \PG\Notifications\Task\NotificationFieldProvider::class
    ];
}