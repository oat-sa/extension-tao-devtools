<?php
/**
 * Represents a single call of a method
 */

namespace oat\taoDevTools\models\Monitor\Chunk;

class CallChunk
{

    /**
     * @var float
     */
    protected $time;

    /**
     * @var \common_monitor_Chunk_Method
     */
    protected $method;

    /**
     * Parameters passed to the call
     * @var array
     */
    protected $params = [];

    /**
     * Hash representation of the parameter array
     * @var string
     */
    protected $hash;

    /**
     * Duplicated status
     * @var bool
     */
    protected $duplicated = false;

    /**
     * Stack trace of this call
     * @var array
     */
    protected $trace = [];

    /**
     * Caller Trace
     * @var string
     */
    protected $callerTrace;

    /**
     * Constructor
     * @param common_monitor_Chunk_Method  $method
     * @param array $params
     */
    public function __construct(MethodChunk $method, array $params) {

        if(function_exists('xdebug_get_function_stack')) {
            $this->trace = xdebug_get_function_stack();
        }

        $this->method = $method;

        $this->params = $params;

    }

    /**
     * Return hash of this call
     * @return string
     */
    public function getHash() {

        if(!$this->hash) {
            $this->hash =  md5(json_encode($this->params));
        }
        return $this->hash;
    }

    /**
     * Set the duplicated status
     * @param $value
     */
    public function setDuplicated($value = true) {
        $this->duplicated = $value;
        return $this;

    }

    /**
     * Return whether this call is duplicated or not
     * @return bool
     */
    public function isDuplicated() {
        return $this->duplicated;
    }

    /**
     * @return common_monitor_Chunk_Method
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getTrace() {
        return $this->trace;
    }

    /**
     * Return information about the file from which the call occur
     * @return string
     */
    public function getCallerTrace() {

        if(!$this->callerTrace && (count($this->trace) > 3)) {
            $this->callerTrace = array_reverse($this->trace);
            $this->callerTrace = array_slice($this->callerTrace, 4);
        }

        return $this->callerTrace;
    }

    /**
     * @return array
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * Start to calculate the time spent
     */
    public function startTiming() {
        $this->time = microtime(true);
    }

    /**
     * Stop to calculate the time spent
     */
    public function stopTiming() {
        $this->time = microtime(true) - $this->time;
    }

    /**
     * Time spent in second
     * @return float
     */
    public function getElapsedTime() {
        return $this->time;
    }
}