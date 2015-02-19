<?php
/**
 * Interface for monitor adapter
 *
 * This adapter must be used to store or display monitored data
 * The monitor could have more than one adapter.
 */

namespace oat\taoDevTools\models\Monitor\OutputAdapter;

use oat\taoDevTools\models\Monitor\Chunk\CallChunk;
use oat\taoDevTools\models\Monitor\Chunk\RequestChunk;
use oat\taoDevTools\models\Monitor\Monitor;

abstract class AbstractAdapter
{

    /**
     * @param array $config
     */
    public function __construct(array $config = null) {

        if(!is_null($config)) {
            foreach($config as $key => $value) {
                $method = 'set' . ucfirst($key);
                if(method_exists($this, $method)) {
                    $this->$method($value);
                }
            }
        }

        $this->init();
    }

    /**
     * Delegate constructor
     */
    public function init() {

    }

    /**
     * Called by the monitor at construct time
     * @param RequestChunk $request
     *
     * @return mixed
     */
    abstract public function startMonitoring(RequestChunk $request);

    /**
     * Called by the monitor at destruct time
     * @param RequestChunk $request
     *
     * @return mixed
     */
    abstract public function endMonitoring(RequestChunk $request);

    /**
     * Called by the monitor for each monitored calls
     * @param CallChunk $call
     *
     * @return mixed
     */
    public function appendCall(CallChunk $call) {}

    /**
     * Get the duplicated Call grouped by method in an array
     * @param $calls
     *
     * @return array
     */
    public function getMergedDuplicatedCalls($calls) {

        $mergedCalls = [];

        foreach($calls as $call) {

            $hash = $call->getHash();

            if(!isset($mergedCalls[$hash])) {
                $mergedCalls[$hash] = [];
            }

            $mergedCalls[$hash][] = $call;
        }
        return $mergedCalls;
    }

    /**
     * Return the minus range of all passed traces
     * @param array $mergedTraces
     *
     * @return int
     */
    public function getTraceMinSize(array $mergedTraces) {

        if(!count($mergedTraces)) {
            return 0;
        }

        $result = 99999999;

        foreach($mergedTraces as $mergedTrace) {
            $size = count($mergedTrace['trace']);
            if($size < $result) {
                $result = $size;
            }
        }
        return $result;
    }

    /**
     * Return the common starting part of all trace
     *
     * @param array $trace
     *
     * @return array
     */
    public function getCommonTracePart(array $mergedTraces, $fromStart = true) {

        $count = count($mergedTraces);
        if(!$count) {
            return [];
        } elseif($count == 1) {
            return current($mergedTraces)['trace'];
        }


        $traces = array_map(

            function($e) use ($fromStart) {

                $trace = $fromStart ? $e['trace'] : array_reverse($e['trace']);
                return array_map(function ($t) {

                    return md5(json_encode($t));

                }, $trace);

            }
            , array_values($mergedTraces));



        $commonParts = count(call_user_func_array([$this,'array_intersect_assoc_strict'],  $traces));

        $referenceTrace = ($fromStart ? current($mergedTraces)['trace'] : array_reverse(current($mergedTraces)['trace']));

        $result = array_slice( $referenceTrace, 0, $commonParts, true);


        return $fromStart ? $result : array_reverse($result);
    }

    /**
     * Same as array_intersect_assoc but stop to compare after one difference
     * @return array
     */
    protected function array_intersect_assoc_strict() {

        $args = func_get_args();
        $arrayCount = count($args);
        // Compare entries

        $intersect = array();



        foreach ($args[0] as $key => $value) {

            $intersect[$key] = $value;

            for ($i = 1; $i < $arrayCount; $i++) {

                if (!isset($args[$i][$key]) || $args[$i][$key] != $value) {

                    unset($intersect[$key]);

                    break 2;

                }

            }

        }

        return $intersect;
    }

    /**
     * Merge and count all identical traces from a group of call
     * @param CallChunk[] $calls
     *
     * @return array
     */
    public function getMergedTrace(array $calls) {

        $hashes = [];

        foreach($calls as $call) {

            $traces =  $call->getCallerTrace();

            $hash = md5(json_encode($traces));

            if(!isset($hashes[$hash])) {
                $hashes[$hash] = ['count' => 1, 'trace' => $traces];
            } else {
                $hashes[$hash]['count']++;
            }
        }
        return $hashes;
    }

    /**
     * Return merged traces information
     *
     *
     * Avoid erroneous common traces detection in reverse way:
     *
     *    t1   t2
     * ------------------
     *  0 A     A
     *  1 B     B
     *  2 C     C
     *  3 B     D
     *  4 C     E
     *  5 D     F
     *  6 E     G
     *  7 F
     *  8 G
     *
     *  In this sequence the starting parts is easy to detect (A-B-C) but
     * the ending one is more difficult due to the repetition of B-C in
     * t1. If you compare this two traces from the end you will find that
     * the ending parts will override the starting one so we cut it.
     *
     * @param $calls
     *
     * @return array
     */
    public function getMergedTraceInfo($calls) {


        $mergedTraces = $this->getMergedTrace($calls);
        $commonStartingParts = $this->getCommonTracePart($mergedTraces);
        $commonEndingParts = $this->getCommonTracePart($mergedTraces, false);

        $minSize = $this->getTraceMinSize($mergedTraces);
        $nbrStartingParts   = count($commonStartingParts);
        $nbrEndingParts     = count($commonEndingParts);
        $commonStartingTrace = ($nbrStartingParts) ? $commonStartingParts[$nbrStartingParts-1] : null;
        $commonEndingTrace = ($nbrEndingParts) ? $commonEndingParts[0]: null;
        if($minSize < ($nbrEndingParts + $nbrStartingParts) ) {
            $offset = ($nbrEndingParts + $nbrStartingParts) - $minSize;
            $nbrEndingParts -= $offset;
            $commonEndingParts = array_slice($commonEndingParts, $offset);
        }
        $umlSrc = $this->getUmlImgUrl($mergedTraces, $nbrStartingParts, $nbrEndingParts,$commonStartingTrace, $commonEndingTrace);
        $count = count($calls);

        foreach($mergedTraces as &$mergedTrace) {
            $mergedTrace['diffTrace'] = array_slice($mergedTrace['trace'], $nbrStartingParts , - $nbrEndingParts );
            foreach($mergedTrace['diffTrace'] as &$trace) {
                $trace['methodSrc'] = $this->getMethodCodeFromTrace($trace, '</pre><span style="color:red;">', '</span><pre>');
            }
        }

        return array(
            'count'                     => $count,
            'params'                    => $count ? $calls[0]->getParams() : array(),
            'mergedTraces'              => $mergedTraces,
            'commonEndingParts'         => $commonEndingParts,
            'commonStartingParts'       => $commonStartingParts,
            'umlSrc'                    => $umlSrc,
        );
    }

    /**
     * Return the source code of a method
     * @param        $trace
     * @param string $methodPrefix
     * @param string $methodSuffix
     *
     * @return array
     */
    public function getMethodCodeFromTrace($trace, $methodPrefix = '', $methodSuffix = '') {

        $lines = file($trace['file']);
        $count = 0;
        $lastFunctionLine = null;
        $lastFunctionName = 'unknown';
        $methodBuffer = '';
        $found = false;
        $level = 0;
        foreach($lines as &$line) {
            $matches = null;
            $level += substr_count($line,'{') - substr_count($line,'}');
            if(preg_match('/(public|private|protected|) *function *(.*) *\(/',$line ,$matches )) {
                //A new function declaration was found...
                if(!$found) {
                    $lastFunctionLine = $count;
                    $lastFunctionName = $matches[2];
                    $methodBuffer = $line;
                } else {
                    $methodBuffer .= $line;
                }
            } else {
                $methodBuffer .= $line;
                if($found && $level == 1) {
                    break;
                }
            }

            if((!$found) && (($count + 1) == $trace['line'])) {
                $found = true;
            }
            $count++;
        }

        return
        [
            'startOffset' => $lastFunctionLine,
            'src' => implode('',array_slice($lines, $lastFunctionLine, ($count +1) - $lastFunctionLine))
        ];

    }

    /**
     * Return the Yuml formated string of a merged traces
     * @param $mergedTraces
     * @param $nbrStartingParts
     * @param $nbrEndingParts
     * @param $startCommonTrace
     * @param $endCommonTrace
     *
     * @return string
     */
    public function getUmlImgUrl($mergedTraces, $nbrStartingParts, $nbrEndingParts, $startCommonTrace, $endCommonTrace) {

        //(start)-label><a>[kettle empty]->(Fill Kettle)->(Boil Kettle),<a>[kettle full]->(Boil Kettle)->(end)

        $result = [];
        $count = 0;
        foreach($mergedTraces as $mergedTrace) {
            $diffTrace = array_slice($mergedTrace['trace'], $nbrStartingParts , - $nbrEndingParts );
            $diffTrace = array_reverse($diffTrace);
            $traceDiagram = [];
            if($startCommonTrace) {
                $traceDiagram[] = $this->umlRenderTrace($endCommonTrace);
            }
            foreach($diffTrace as $trace) {

                $traceDiagram[] = $this->umlRenderTrace($trace);
            }
            if($endCommonTrace) {
                $traceDiagram[] = $this->umlRenderTrace($startCommonTrace);
            }
            //$traceDiagram[] = '(end)';
            //$result[] = '(start)- ' . ++$count . ' >' . implode('->', $traceDiagram);
            $result[] = implode('-' . $mergedTrace['count'] . '>', $traceDiagram);
        }

        return 'http://yuml.me/diagram/plain/activity/' . implode(',', $result);


    }

    /**
     * Return the Scruffy representation of this trace
     * @param $trace
     *
     * @return string
     */
    protected function umlRenderTrace($trace) {
        $file = substr($trace['file'], strlen(ROOT_PATH));
        $explodedFile = explode('/', $file);
        $file = array_pop($explodedFile);
        $path = implode('/', $explodedFile);
        return '(' .  $path . '\r' . $file . '::' .  $trace['line'] . '\r' . $trace['function'] . ')';

    }
}