<?php
/**
 * Used to monitor method call
 */
namespace oat\taoDevTools\models\Monitor;

use oat\taoDevTools\models\Monitor\Chunk\CallChunk;
use oat\taoDevTools\models\Monitor\Chunk\RequestChunk;
use oat\taoDevTools\models\Monitor\OutputAdapter\AbstractAdapter;
use oat\taoDevTools\models\Monitor\Exception\BadAdapterConfigException;

class Monitor
{

    const PERSISTENCE_ID = 'monitor';

    const PERSISTENCE_ID_KEY = 'persistenceId';

    /**
     * @var common_monitor_Monitor
     */
    private static $instance;

    /**
     * @var bool
     */
    protected $enabled = false;

    /**
     * @var AbstractAdapter[]
     */
    protected $adapters = array();

    /**
     * @var common_monitor_Chunk_Request
     */
    protected $request;

    /**
     * Persistence proxy mapping configuration
     * @var array
     */
    protected $proxyPersistenceMap = array();

    private function __clone(){}

    /**
     * This constructor is public to let unit test the Monitor
     * @param array $config
     */
    public function __construct(array $config = null){

        if(!is_null($config)) {
            foreach($config as $key => $value) {
                $method = 'set' . ucfirst($key);
                if(method_exists($this, $method)) {
                    $this->$method($value);
                }

            }
        }

    }

    /**
     * @param boolean $enabled
     *
     * @return common_monitor_Monitor
     */
    public function setEnabled($enabled) {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * True if the monitor is enabled
     * @return bool
     */
    public function isEnabled() {
        return $this->enabled;
    }

    /**
     * Singleton entry point
     * @return Monitor
     */
    public static function getInstance() {

        if(is_null(self::$instance)) {

            if(false === ($config = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDevTools')->getConfig('monitor'))) {
                   $config = [];
            }

            self::$instance = new self($config);

            if(self::$instance->isEnabled()) {
                self::$instance->installProxy();
                //Load additional report command adapters
                foreach(self::$instance->adapters as $adapter) {
                    $adapter->startMonitoring(self::$instance->getRequest());
                }
            } else {
                self::getInstance()->uninstallProxy();
            }
        }

        return self::$instance;
    }

    /**
     * Install the proxy if he is not already installed
     * @throws \Exception
     */
    public function installProxy() {

        $generisExtension = \common_ext_ExtensionsManager::singleton()->getExtensionById('generis');

        if(!$persistenceConfig = $generisExtension->getConfig('persistences')) {
            throw new \Exception('There is no "persistences" configuration');
        }

        $configDirty = false;

        foreach($this->getProxyPersistenceMap() as $key => $config) {
            if(isset($persistenceConfig[$key]) && //Is there a persistence fot this map?
                ((!isset($persistenceConfig[$key]['persistenceId'])) || //Is this persistence not already wrapped ?
                    (!isset($persistenceConfig[$persistenceConfig[$key][self::PERSISTENCE_ID_KEY]])) //If yes, is the wrapped configuration not present?
                )
            ) {
                //generate a unique id for the wrapped configuration
                while(isset($persistenceConfig[$randomKey = 'wrapped_' . $key . '_' . rand(999, 9999)]));

                //Add to the proxy configuration the unique persistence id of the wrapped configuration
                $config[self::PERSISTENCE_ID_KEY] = $randomKey;

                //Switch the two persistence configuration
                $persistenceConfig[$randomKey] = $persistenceConfig[$key];
                $persistenceConfig[$key] = $config;

                //set the dirty flag
                $configDirty = true;
            }

        }

        if($configDirty) {
            $generisExtension->setConfig('persistences', $persistenceConfig);
        }
    }

    /**
     * Uninstall the proxy if he is installed
     */
    public function uninstallProxy() {

        $generisExtension = \common_ext_ExtensionsManager::singleton()->getExtensionById('generis');

        if(!$persistenceConfig = $generisExtension->getConfig('persistences')) {
            throw new \Exception('There is no "persistences" configuration');
        }

        $configDirty = false;

        foreach($this->getProxyPersistenceMap() as $key => $config) {

            if(isset($persistenceConfig[$key]) &&
                (isset($persistenceConfig[$key][self::PERSISTENCE_ID_KEY]) &&
                    isset($persistenceConfig[$persistenceConfig[$key][self::PERSISTENCE_ID_KEY]]))) {

                $persistenceId = $persistenceConfig[$key][self::PERSISTENCE_ID_KEY];
                $persistenceConfig[$key] = $persistenceConfig[$persistenceId];
                unset($persistenceConfig[$persistenceId]);
                $configDirty = true;
            }
        }

        if($configDirty) {
            $generisExtension->setConfig('persistences', $persistenceConfig);
        }
    }

    /**
     * Add monitoring data
     *
     * @param mixed  $target : Instance from which the data come from
     * @param string $method : Method name
     * @param array  $params : Parameters array
     * @return CallChunk
     */
    public function append($target, $method, array $params) {

        if(!$this->isEnabled()) {
            return;
        }

        $call = $this->getRequest()
            ->getClass(get_class($target))
            ->getInstance($target)
            ->getMethod($method)
            ->appendCall($params);

        foreach($this->getAdapters() as $adapter) {
            $adapter->appendCall($call);
        }
        return $call;
    }

    /**
     * Set adapters
     * @param array $adapters
     */
    public function setAdapters(array $adapters) {

        foreach($adapters as $name => $adapter) {
            $this->addAdapter($name, $adapter);
        }
    }

    /**
     * Add an output adapter to the monitor
     *
     * @param AbstractAdapter|array $adapter
     *
     * @throws Exception\BadAdapterConfig
     */
    public function addAdapter($name, $adapter) {

        if($adapter instanceof AbstractAdapter) {
            $this->adapters[$name] = $adapter;
        } elseif(is_array($adapter)) {
            $this->adapters[$name] = $this->adapterFactory($name, $adapter);
        } else {
            throw new BadAdapterConfig('Adapter config must be an AdapterAbstract instance or an array, ' . gettype($adapter) . ' received');
        }

    }

    /**
     * Return an adapter by name
     * @param $name
     *
     * @return null|AbstractAdapter
     */
    public function getAdapter($name) {
        return isset($this->adapters[$name]) ? $this->adapters[$name] : null;
    }

    /**
     * @param array $config
     *
     * @return AbstractAdapter
     * @throws Exception\BadAdapterConfig
     */
    public function adapterFactory($name, array $config) {

        $className = 'oat\taoDevTools\models\Monitor\OutputAdapter\\' . ucfirst($name);
        if(!class_exists($className)) {
            $className = $name;
            if(!class_exists($className)) {
                throw new BadAdapterConfigException('Could not find any monitor adapter with name :' . $name);
            }
        }
        return new $className($config);
    }

    /**
     * @return RequestChunk
     */
    public function getRequest() {
        if(is_null($this->request)) {
            $this->request = new RequestChunk();
        }
        return $this->request;
    }

    /**
     * @return AbstractAdapter[]
     */
    public function getAdapters() {
        return $this->adapters;
    }

    /**
     * @param array $proxyPersistenceMap
     *
     * @return Monitor
     */
    public function setProxyPersistenceMap($proxyPersistenceMap) {
        $this->proxyPersistenceMap = $proxyPersistenceMap;

        return $this;
    }

    /**
     * @return array
     */
    public function getProxyPersistenceMap() {
        return $this->proxyPersistenceMap;
    }

    /**
     * Destructor
     */
    public function __destruct() {

        if(!$this->isEnabled()) {
            return;
        }

        foreach($this->adapters as $adapter) {
            $adapter->endMonitoring($this->request);
        }
    }
}