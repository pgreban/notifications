<?php

namespace PG\Notifications\Task;

use TYPO3\CMS\Backend\Utility\BackendUtility;

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
 * Field provider for Extbase CommandController Scheduler task
 */
class NotificationFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface
{

    /**
     * @var \TYPO3\CMS\Extbase\Scheduler\Task
     */
    protected $task;


    /**
     * Render additional information fields within the scheduler backend.
     *
     * @param array &$taskInfo Array information of task to return
     * @param mixed $task \TYPO3\CMS\Scheduler\Task\AbstractTask or \TYPO3\CMS\Scheduler\Execution instance
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the calling object (BE module of the Scheduler)
     * @return array Additional fields
     * @see \TYPO3\CMS\Scheduler\AdditionalFieldProvider#getAdditionalFields($taskInfo, $task, $schedulerModule)
     */
    public function getAdditionalFields(
        array &$taskInfo,
        $task,
        \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule
    ) {
        $this->task = $task;
        $arguments = [];
        if ($this->task !== null) {
            $this->task->setScheduler();
            $arguments = $this->task->getArguments();
        }
        $fields = array();
        $fields['userGroup'] = $this->getUsergroupField($arguments['userGroup']);
        $fields['recipient'] = $this->renderField(
            'recipient',
            'Recipient email address',
            isset($arguments['recipient']) ? $arguments['recipient'] : null
        );
        $fields['timeRangeStart'] = $this->renderField(
            'timeRangeStart',
            'The start point to send the notifications for. This string is passed to the constructor of \DateTime() so use a compatible format like "now", "yesterday" etc',
            isset($arguments['timeRangeStart']) ? $arguments['timeRangeStart'] : 'now -1 day'
        );
        $fields['timeRangeEnd'] = $this->renderField(
            'timeRangeEnd',
            'The end point to send the notifications for. This string is passed to the constructor of \DateTime() so use a compatible format "now"',
            isset($arguments['timeRangeEnd']) ? $arguments['timeRangeEnd'] : 'now'
        );
        $fields['notifyOnNoChanges'] = $this->renderField(
            'notifyOnNoChanges',
            'Send notification even if nothing has changed',
            isset($arguments['notifyOnNoChanges']) ? $arguments['notifyOnNoChanges'] : false,
            'checkbox'
        );

        if (count($arguments)) {
            $this->task->save();
        }
        return $fields;
    }

    /**
     * Gets a select field for usergroups
     *
     * @var int $currentGroup
     * @return array
     */
    protected function getUsergroupField($currentGroup)
    {
        $groups = BackendUtility::getGroupNames();

        $options = [];
        foreach ($groups as $group) {
            $options[$group['uid']] = $group['title'];
        }

        return $this->renderField(
            'userGroup',
            [
                'options' => $options,
                'label' => 'Usergroup'
            ],
            $currentGroup,
            'select'
        );
    }

    /**
     * Renders a field for defining an argument's value
     *
     * @param string $name
     * @param mixed $configuration
     * @param mixed $currentValue
     * @param string $type
     * @return array
     */
    protected function renderField($name, $configuration, $currentValue = null, $type = 'input')
    {
        if (!is_array($configuration)) {
            $configuration = array(
                'label' => $configuration
            );
        }

        $fieldName = 'tx_scheduler[task_notifications][' . $name . ']';

        switch ($type) {
            case 'select':
                $html = $this->renderSelectField($fieldName, $configuration['options'], $currentValue);
                break;
            case 'checkbox':
                $html = $this->renderCheckboxField($fieldName, $currentValue);
                break;
            default:
            case 'input':
                $html = $this->renderInputField($fieldName, $currentValue);
                break;
        }

        return [
            'code' => $html,
            'label' => $configuration['label']
        ];
    }

    /**
     * Render a select field with name $name and options $options
     *
     * @param string $fieldName
     * @param array $options
     * @param string $selectedOptionValue
     * @return string
     */
    protected function renderSelectField($fieldName, array $options, $selectedOptionValue)
    {
        $html = array(
            '<select class="form-control" name="' . $fieldName . ']">'
        );
        foreach ($options as $optionValue => $optionLabel) {
            $selected = $optionValue == $selectedOptionValue ? ' selected="selected"' : '';
            array_push($html,
                '<option value="' . htmlspecialchars($optionValue) . '"' . $selected . '>' . htmlspecialchars($optionLabel) . '</option>');
        }
        array_push($html, '</select>');
        return implode(LF, $html);
    }

    protected function renderCheckboxField($fieldName, $currentValue)
    {
        $html = '<input type="hidden" name="' . $fieldName . '" value="0">';
        $html .= '<div class="checkbox"><label><input type="checkbox" name="' . $fieldName . '" value="1" ' . ((bool)$currentValue ? ' checked="checked"' : '') . '></label></div>';
        return $html;
    }

    protected function renderInputField($fieldName, $currentValue)
    {
        return '<input class="form-control" type="text" name="' . $fieldName . '" value="' . htmlspecialchars($currentValue) . '" /> ';
    }

    /**
     * Validates additional selected fields
     *
     * @param array &$submittedData
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule
     * @return bool
     */
    public function validateAdditionalFields(
        array &$submittedData,
        \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule
    ) {
        return true;
    }

    /**
     * Saves additional field values
     *
     * @param array $submittedData
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task
     * @return bool
     */
    public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task)
    {
        $arguments = (array)$submittedData['task_notifications'];
        $task->setArguments($arguments);
        return true;
    }

}
