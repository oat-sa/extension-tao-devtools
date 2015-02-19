<?php
/**
 * Represents a request. The request is the main container
 * of the monitored elements.
 */

namespace oat\taoDevTools\models\Monitor\Chunk;

use oat\taoDevTools\models\Monitor\Chunk\Call\AbstractContainer;
use oat\taoDevTools\models\Monitor\Chunk\Method\ContainerTrait;

class RequestChunk extends AbstractContainer
{
    use ContainerTrait;

    /**
     * Uri of the request
     * @var string
     */
    protected $uri = 'unknown';

    /**
     * Monitored Classes
     * @var ClassChunk[]
     */
    protected $classes = [];

    /**
     * @var string unique identifier
     */
    protected $id;

    /**
     * Constructor
     */
    public function __construct() {
        $this->uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI']: 'n/a';
        $this->id = md5($this->uri);
    }

    /**
     * Class factory
     * @param $className
     *
     * @return ClassChunk
     */
    public function classFactory($className) {
        return new ClassChunk($this, $className);
    }

    /**
     * Return a chunk class based of the classname
     * @param $className
     *
     * @return ClassChunk
     */
    public function getClass($className) {

        return isset($this->classes[$className]) ?
            $this->classes[$className] :
            $this->classes[$className] =  $this->classFactory($className);
    }

    /**
     * Return the request uri
     * @return string
     */
    public function getUri() {
        return $this->uri;
    }

    /**
     * @return ClassChunk[]
     */
    public function getClasses() {
        return $this->classes;
    }

    /**
     * Return the url of the current request
     * @return string
     */
    public function getUrl() {

        $scheme =  isset($_SERVER['HTTPS']) | isset($_SERVER['https']) ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $port = (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] != 80)) ? ':' . $_SERVER['SERVER_PORT'] : '';

        return $scheme . $host . $port . $this->getUri();
    }

    /**
     * Return a string representation of the current request url
     * @return mixed
     */
    public function getUrlSlug() {
        $replacedChar = ['/', '?', '&', '=', '*', ' ', '%', '<','>', '/', ':', '\\', '\'', '"', '.'];
        return str_replace($replacedChar, '_',$this->getUri());
    }

    /**
     * @return string
     */
    public function getId() {
        return $this->id;
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