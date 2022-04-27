<?php
namespace PG\Notifications\ViewHelpers\Tca;

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

class TableNameViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper {

    use CompileWithRenderStatic;

    /**
     * @return void
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('tablename', 'string', 'The name of the TCA table to get the translated name/label for', true);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        if (!isset($GLOBALS['TCA'][$arguments['tablename']])) {
            throw new \Exception('unknown tablename. Table not defined in TCA');
        }
        return LocalizationUtility::translate($GLOBALS['TCA'][$arguments['tablename']]['ctrl']['title'], 'Notifications');
    }
}