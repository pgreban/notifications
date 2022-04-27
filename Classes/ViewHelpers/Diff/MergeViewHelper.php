<?php
namespace PG\Notifications\ViewHelpers\Diff;

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

class MergeViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper {

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
        $this->registerArgument('recordHistory', 'array', 'The collection of sys_history changes of one content element', true);
        $this->registerArgument('as', 'string', 'Name of the variable to assign the result to', false);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return array|string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $history = $arguments['recordHistory'];
        $output = array_shift($history);
        while (count($history)) {
            $override = array_shift($history);
            if ($override['tstamp'] < $output['tstamp']) {
                $output['oldRecord'] = array_merge($output['oldRecord'], $override['oldRecord']);
                $output['newRecord'] = array_merge($override['newRecord'], $output['newRecord']);
            } else {
                $output['oldRecord'] = array_merge($override['oldRecord'], $output['oldRecord']);
                $output['newRecord'] = array_merge($output['newRecord'], $override['newRecord']);
            }
        }

        if ($arguments['as']) {
            $renderingContext->getVariableProvider()->add($arguments['as'], $output);
            $output = $renderChildrenClosure();
            $renderingContext->getVariableProvider()->remove($arguments['as']);
            return $output;
        }

        return $output;
    }
}