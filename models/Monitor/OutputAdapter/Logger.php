<?php

/**
 * Class common_monitor_Adapter_Logger
 * Render the monitor statistics to the common_logger
 */

namespace oat\taoDevTools\models\Monitor\OutputAdapter;

use oat\taoDevTools\models\Monitor\Chunk\CallChunk;
use oat\taoDevTools\models\Monitor\Chunk\RequestChunk;

class Logger extends  AbstractAdapter
{

    protected $loggerTags = ['MONITOR'];

    protected $colorize = true;

    protected $fallbackColor = "0;97";

    protected $buffer = '';

    protected $tabsChar = "  ";

    /**
     * Called by the monitor at construct time
     *
     * @param RequestChunk $request
     *
     * @return mixed
     */
    public function startMonitoring(RequestChunk $request) {
        $this->log('Start Monitoring : ' . $this->color($request->getUri(), 95) . PHP_EOL);
    }

    /**
     * Called by the monitor at destruct time
     *
     * @param RequestChunk $request
     *
     * @return mixed
     */
    public function endMonitoring(RequestChunk $request) {

        $this->renderStatistics($request);
        $this->flush();
        $this->log('End Monitoring : ' . $this->color($request->getUri(), 92) . PHP_EOL);
        //$this->log('Current buffer size : ' . $this->color(strlen($this->buffer), 92) . PHP_EOL);


    }

    /**
     * Write directly to the logger
     * @param $message
     */
    protected function log($message) {
        common_Logger::d($message, $this->loggerTags);
    }

    /**
     * Aggregate data to the output buffer
     * @param int    $tabs
     * @param string $message
     */
    protected function output($tabs = 0,$message = '') {
        $this->buffer .= str_repeat($this->tabsChar, $tabs) . $message  . PHP_EOL;

    }

    /**
     * Flush the output buffer to the logger
     * buffer size could not be more than 65535 bytes
     * So you need to flush time to time
     */
    protected function flush() {
        $this->log($this->buffer);
        $this->buffer = '';
    }

    /**
     * Render the statistics
     * @return string
     */
    public function renderStatistics(RequestChunk $request) {

        $this->output(0, $this->color('Statistics',94,40,4));

        foreach($request->getClasses() as $className => $class) {

            $this->output(1, "Class: " . $this->color($className,95,40,4));

            $this->output(2, $this->color('By objects:',96));

            $this->renderClassStatistics($class->getInstances(), 3);

            $this->output(2, $this->color('By methods:',96));

            $this->renderMethodStatistics($class->getMethods(), 3);

            $this->output();

        }

    }

    /**
     * Render the Class statistics
     *
     * @param $objectClass
     * @param $classStatistics
     *
     * @return string
     */
    protected function renderClassStatistics(array $instances, $tabs, $renderTrace = true) {


        foreach($instances as $objectId => $instance) {

            $this->output($tabs, "Object: " . $this->color($objectId,93) . ' (' . $instance->getRdfUri() . ')');

            $this->renderMethodStatistics($instance->getMethods(), 4, $renderTrace);

            $this->output();

            $this->flush();
        }

    }

    /**
     * Render the method statistics
     *
     * @param array  $methods
     * @param string $tabs
     *
     * @return string
     */
    protected function renderMethodStatistics(array $methods, $tabs, $renderTrace = true) {


        foreach($methods as $methodName => $method) {

            $nbrCalls = count($method->getCalls());
            $nbrDuplicatedCalls = count($method->getDuplicatedCalls());


            $this->output($tabs, " => " . $this->color($methodName, 35) . ' : '
            . $nbrCalls . '/'
            . $nbrDuplicatedCalls . ' ('
            . ($nbrDuplicatedCalls ? $this->color(sprintf('%.2f',((100 / $nbrCalls) * $nbrDuplicatedCalls)),91) . '%' : "0%") . ')'
            );

            if($nbrDuplicatedCalls && $renderTrace) {


                $duplicatedCalls = $this->getMergedDuplicatedCalls($method->getDuplicatedCalls());
                foreach($duplicatedCalls as $hash => $calls){
                    $this->output($tabs + 1, $this->color('Parameters : ', 96)  .  '(' . count($calls) . ($calls ? ' calls)':' call)') . PHP_EOL . $this->color(print_r($calls[0]->getParams(), 92), true));
                    if($renderTrace) {
                        $this->renderTraces($calls, $tabs);
                    }
                }
            }
        }
    }

    /**
     * Render a merged representation of traces from a group of call
     * @param array $calls
     * @param int   $tabs
     */
    public function renderTraces(array $calls, $tabs = 0) {

        $mergedTraces = $this->getMergedTrace($calls);

        $commonStartingParts = $this->getCommonTracePart($mergedTraces);
        $commonEndingParts = $this->getCommonTracePart($mergedTraces, false);

        $this->output($tabs + 1, $this->color('Common starting trace : ', 92));

        $count = 1;
        foreach($commonStartingParts as $trace ) {
            $this->output($tabs + 2, str_repeat(' ', $count++) . '=> ' . $this->color($trace['function'], 91) . ' [' . $trace['file'] . ' (' . $trace['line'] . ')]');
        }

        $this->output();

        foreach($mergedTraces as $mergedTrace) {
            $this->output($tabs + 2, $this->color('Nbr call for this trace : ', 96) . $mergedTrace['count']);
            $count = 1;

            $diffTrace = array_slice($mergedTrace['trace'], count($commonStartingParts) , - count($commonEndingParts));

            foreach($diffTrace as $trace ) {
                $function = isset($trace['function']) ? $trace['function'] : 'unknown';
                $file = isset($trace['file']) ? $trace['file'] : 'unknown';
                $line = isset($trace['line']) ? $trace['line'] : 'unknown';

                $this->output($tabs + 3, str_repeat(' ', $count++) . '=> ' . $this->color($function, 91) . ' [' . $file . ' (' . $line . ')]');
            }

            $this->output();
        }

        $this->output($tabs + 1, $this->color('Common ending trace : ', 92));

        $count = 1;
        foreach($commonEndingParts as $trace ) {
            $this->output($tabs + 2, str_repeat(' ', $count++) . '=> ' . $this->color($trace['function'], 91) . ' [' . $trace['file'] . ' (' . $trace['line'] . ')]');
        }


    }

    /**
     * Decorate the passed text with shell color information
     * @param string $text
     * @param int    $fore
     * @param int    $back
     * @param int    $effect
     *
     * @return string
     */
    public function color($text, $fore, $back = 40, $effect = 0) {

        return $this->colorize ? "\033[" . $effect . ';' . $fore . ';' . $back . 'm' . $text . "\033[" . $this->fallbackColor . "m" : $text;

    }

    /**
     * @param boolean $colorize
     *
     * @return common_monitor_Adapter_Logger
     */
    public function setColorize($colorize) {
        $this->colorize = $colorize;

        return $this;
    }

    /**
     * @param string $fallbackColor
     *
     * @return common_monitor_Adapter_Logger
     */
    public function setFallbackColor($fallbackColor) {
        $this->fallbackColor = $fallbackColor;

        return $this;
    }

    /**
     * @param string $tabsChar
     *
     * @return common_monitor_Adapter_Logger
     */
    public function setTabsChar($tabsChar) {
        $this->tabsChar = $tabsChar;

        return $this;
    }

    /**
     * @param array $loggerTags
     *
     * @return common_monitor_Adapter_Logger
     */
    public function setLoggerTags($loggerTags) {

        if(!is_array($loggerTags)) {
            $loggerTags = [$loggerTags];
        }

        $this->loggerTags = $loggerTags;

        return $this;
    }



}