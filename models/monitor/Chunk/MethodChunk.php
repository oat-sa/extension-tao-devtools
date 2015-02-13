<?php
/**
 * Represents a method of the monitored code
 */

namespace oat\taoDevTools\models\Monitor\Chunk;

use oat\taoDevTools\models\Monitor\Chunk\Call\AbstractContainer;

class MethodChunk extends AbstractContainer
{

    protected $methodName = 'undefined';

    /**
     * @var array
     */
    protected $callHashes = [];

    /**
     * @var string hash
     */
    protected $id;

    /**
     * Constructor
     * @param InstanceChunk $instance
     * @param                            $methodName
     */
    public function __construct($instance, $methodName) {

        $this->parentChunk = $instance;
        $this->methodName = $methodName;

    }

    /**
     *
     * @param array $params
     * @return CallChunk
     */
    public function appendCall(array $params) {

        $call = $this->callFactory($params);

        $hash = $call->getHash();
        if(isset($this->callHashes[$hash])) {

           $originalCall = $this->callHashes[$hash];
           $originalCall->setDuplicated();
           $this->addDuplicatedCall($originalCall);
           $call->setDuplicated();
        } else {
            $this->callHashes[$hash] = $call;
        }

        $this->addCall($call);

        return $call;
    }

    /**
     * Return a hash id of this method
     * @return string
     */
    public function getId() {
        if(!$this->id) {
            $this->id = md5($this->methodName);
        }
        return $this->id;
    }


    /**
     * Call factory
     * @param array $params
     *
     * @return CallChunk
     */
    public function callFactory(array $params = null) {
        return new CallChunk($this, $params);
    }

    /**
     * @return InstanceChunk
     */
    public function getInstance() {
        return $this->parentChunk;
    }

    /**
     * @return string
     */
    public function getMethodName() {
        return $this->methodName;
    }


}

