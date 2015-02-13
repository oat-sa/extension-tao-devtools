<?php
/**
 * Represents an class instance of the monitored code
 */

namespace oat\taoDevTools\models\Monitor\Chunk;

use oat\taoDevTools\models\Monitor\Chunk\Call\AbstractContainer;
use oat\taoDevTools\models\Monitor\Chunk\Method\ContainerTrait;

class InstanceChunk extends AbstractContainer
{
    use ContainerTrait;

    /**
     * @var string object hash
     */
    protected $id = 'unknown';

    /**
     * @var string Rdf Uri
     */
    protected $rdfUri = 'n/a';

    protected $target;

    /**
     * @param ClassChunk $class
     * @param                         $instance
     */
    public function __construct(ClassChunk $class, $instance) {

        $this->target = $instance;
        $this->parentChunk  = $class;
        $this->id       = spl_object_hash($instance);
        $this->rdfUri   = method_exists($instance,'getUri') ? $instance->getUri() : 'n/a';
    }

    /**
     * @return ClassChunk
     */
    public function getClass() {
        return $this->parentChunk;
    }

    /**
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getRdfUri() {
        return $this->rdfUri;
    }

    public function __sleep()
    {
        return array_diff(array_keys(get_object_vars($this)), array('target'));
    }

}