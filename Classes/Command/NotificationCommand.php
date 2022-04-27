<?php

namespace PG\Notifications\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
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
 * A command controller to trigger registered import tasks
 */
class NotificationCommand extends \Symfony\Component\Console\Command\Command
{

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this
            ->setDescription('Sends notifications about content changes of a specific user group')
            ->addArgument(
                'backendUserGroup',
                InputArgument::REQUIRED,
                'The ID of the backend user group the backend changes should be processed for'
            )
            ->addArgument(
                'recipient',
                InputArgument::REQUIRED,
                'The recipient email of the notifications'
            )
            ->addArgument(
                'timeRangeStart',
                InputArgument::OPTIONAL,
                'The start time range to send the notifications for. This string is passed to the constructor of \DateTime() so use a compatible format like "now", "yesterday" etc',
                'today'
            )
            ->addArgument(
                'timeRangeEnd',
                InputArgument::OPTIONAL,
                'The end time range to send the notifications for. If empty, the entire day of "timeRangeStart" is used',
                'tomorrow'
            )
            ->addArgument(
                'notifyOnNoChanges',
                InputArgument::OPTIONAL,
                'If notifications should be sent if no changes occurred',
                false
            );;
    }

    /**
     * Triggers the import service
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        // Ensure the _cli_ user is authenticated
        Bootstrap::initializeBackendAuthentication();

        $notificationService = GeneralUtility::makeInstance(NotificationService::class);
        $success = $notificationService->sendChangeNotifications(
            $input->getArgument('backendUserGroup'),
            $input->getArgument('recipient'),
            $input->getArgument('timeRangeStart'),
            $input->getArgument('timeRangeEnd'),
            $input->getArgument('notifyOnNoChanges')
        );
        if (!$success !== true) {
            $io->error($success);
            throw new \RuntimeException('The notification could not be sent.', 1484484613);
        }

        $message = 'Notifications sent.';
        $io->success($message);
    }
}
