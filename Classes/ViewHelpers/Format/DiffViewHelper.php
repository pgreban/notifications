<?php
namespace PG\Notifications\ViewHelpers\Format;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

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

class DiffViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper {

    use CompileWithRenderStatic;

    /**
     * @var boolean
     */
    protected $escapeChildren = false;

    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @return void
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('old', 'array', 'Array with lat and long as keys containing the geoposition of the point', true);
        $this->registerArgument('new', 'array', 'Array with lat and long as keys containing the geoposition of the point', true);
        $this->registerArgument('table', 'string', 'The name of the DB table this record is from', false, 'tt_content');
        $this->registerArgument('as', 'string', 'Name of the variable to assign the result to', false);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string|array
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $lines = [];
        if (is_array($arguments['old'])) {
            /* @var DiffUtility $diffUtility */
            $diffUtility = GeneralUtility::makeInstance(DiffUtility::class);
            $diffUtility->stripTags = false;
            $fieldsToDisplay = array_keys($arguments['new']);
            foreach ($fieldsToDisplay as $fieldName) {
                if (is_array($GLOBALS['TCA'][$arguments['table']]['columns'][$fieldName]) && $GLOBALS['TCA'][$arguments['table']]['columns'][$fieldName]['config']['type'] !== 'passthrough') {
                    // Create diff-result:
                    $diffres = $diffUtility->makeDiffDisplay(
                        BackendUtility::getProcessedValue($arguments['table'], $fieldName, $arguments['old'][$fieldName], 0, true),
                        BackendUtility::getProcessedValue($arguments['table'], $fieldName, $arguments['new'][$fieldName], 0, true)
                    );
                    $lines[] = [
                        'title' => LocalizationUtility::translate(BackendUtility::getItemLabel($arguments['table'], $fieldName)),
                        'result' => str_replace('\n', PHP_EOL, str_replace('\r\n', '\n', $diffres))
                    ];
                }
            }
        }

        if ($arguments['as']) {
            $renderingContext->getVariableProvider()->add($arguments['as'], $lines);
            $output = $renderChildrenClosure();
            $renderingContext->getVariableProvider()->remove($arguments['as']);
            return $output;
        }

        return $lines;
    }
}