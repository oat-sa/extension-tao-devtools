<?php
/**
 * Represents a class of the monitored code
 */

namespace oat\taoDevTools\models\Monitor\Chunk;

use oat\taoDevTools\models\Monitor\Chunk\Call\AbstractContainer;
use oat\taoDevTools\models\Monitor\Chunk\Method\ContainerTrait;

class ClassChunk extends AbstractContainer
{
    use ContainerTrait;

    protected $className = 'undefined';

    protected $instances = [];

    protected $id;

    /**
     * Constructor
     * @param $className
     */
    public function __construct(RequestChunk $request, $className) {

        $this->parentChunk = $request;
        $this->className = $className;

    }

    /**
     * Instance Factory
     * @param $class
     * @param $target
     *
     * @return InstanceChunk
     */
    public function instanceFactory($target) {
        return new InstanceChunk($this, $target);
    }

    /**
     * Return the the Chunk Instance of the passed target
     * @param $target
     *
     * @return InstanceChunk
     */
    public function getInstance($target) {

        $targetId = spl_object_hash($target);

        return isset($this->instances[$targetId]) ?
            $this->instances[$targetId] :
            $this->instances[$targetId] = $this->instanceFactory($target);

    }

    /**
     * @return common_monitor_Chunk_Request
     */
    public function getRequest() {
        return $this->parentChunk;
    }

    /**
     * @return string
     */
    public function getClassName() {
        return $this->className;
    }

    /**
     * Return a hash id of this class
     * @return string
     */
    public function getId() {
        if(!$this->id) {
            $this->id = md5($this->className);
        }
        return $this->id;
    }

    /**
     * @return InstanceChunk[]
     */
    public function getInstances() {
        return $this->instances;
    }


    /**
     * @param array $params
     */
    public function addCall(CallChunk $call, $recursive = true) {

        $method = $this->getMethod($call->getMethod()->getMethodName(), $call->getMethod()->getInstance());

        $method->addCall($call, false);

        parent::addCall($call, $recursive);

    }

    /**
     * @param array $params
     */
    public function addDuplicatedCall(CallChunk $call, $recursive = true) {

        $method = $this->getMethod($call->getMethod()->getMethodName(), $call->getMethod()->getInstance());

        $method->addDuplicatedCall($call, false);

        parent::addDuplicatedCall($call, $recursive);

    }

}