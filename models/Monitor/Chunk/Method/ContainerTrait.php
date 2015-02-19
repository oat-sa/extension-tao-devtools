<?php
/**
 */

namespace oat\taoDevTools\models\Monitor\Chunk\Method;

use oat\taoDevTools\models\Monitor\Chunk\MethodChunk;

trait ContainerTrait
{

    protected $methods = [];

    /**
     * Chunk method factory
     * @param $link parentChunk
     * @param $methodName the method name
     *
     * @return MethodChunk
     */
    public function methodFactory($methodName, $link) {
        return new MethodChunk($link, $methodName);
    }

    /**
     * Retrieve a Chunk Method by name
     * Create and store it if it does'nt exists
     * @param      $methodName
     * @param null $link
     *
     * @return MethodChunk
     */
    public function getMethod($methodName, $link = null) {

        if(!isset($this->methods[$methodName])) {
            $this->methods[$methodName] = $this->methodFactory($methodName, is_null($link) ? $this : $link);
        }
        return $this->methods[$methodName];
    }

    /**
     * @return array
     */
    public function getMethods() {
        return $this->methods;
    }

}