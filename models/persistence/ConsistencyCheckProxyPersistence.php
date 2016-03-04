<?php
/**
 * Created by PhpStorm.
 * User: siwane
 * Date: 04/03/2016
 * Time: 14:04
 */

namespace oat\taoDevTools\models\persistence;

use common_persistence_Persistence;
use oat\generis\model\data\Model;
use oat\generis\model\kernel\persistence\wrapper\RdfWrapper;
use oat\oatbox\service\ConfigurableService;
use PDO;

class ConsistencyCheckProxyPersistence extends ConfigurableService implements Model
{
    const OPTION_HARD_MODEL = 'default';

    const OPTION_SMOOTH_MODEL = 'smoothsql';

    private $persistences;

    private $persistenceKey;

    /**
     * Get a persistence by name
     *
     * @param $name
     */
    private function addPersistence($name)
    {
        if (empty($this->persistences[$name])) {
            $this->persistences[$name] =
                \common_persistence_SqlPersistence::getPersistence($name);
        }
        return $this->persistences[$name];
    }

    /**
     * Return default driver persistence
     * Init all persistences to compare
     *
     * @param string $id
     * @param array $params
     * @return \common_persistence_SqlPersistence
     */
    function connect($id, array $params)
    {
        $this->persistenceKey = $params['persistenceId'];
        $this->addPersistence($params['persistenceId']);
        unset($params['persistenceId']);

        foreach ($params['toCompare'] as $persistence) {
            $this->addPersistence($persistence);
        }
        unset($params['compareTo']);

        return new \common_persistence_SqlPersistence($params, $this);
    }

    /**
     * Call function for all persistences and put results in array
     *
     * @param $function
     * @param array $args
     * @return array
     */
    function callOnAllPersistences($function, array $args)
    {
        $results = [];
        foreach ($this->persistences as $key => $persistence) {
            $results[$key] = call_user_func_array(array($persistence, $function), $args);
        }
        return $results;
    }

    /**
     * Get results and check if they are equals for all persistences
     *
     * @param $function
     * @param $args
     * @return mixed
     * @throws \Exception
     */
    function getValidResultOnCall($function, $args)
    {
        try {
            $results = $this->callOnAllPersistences($function, $args);
            foreach ($results as $key => $result) {
                foreach ($results as $k => $r) {
                    if ($key == $k && $result != $r) {
                        $this->error($results);
                    }
                }
            }

            return $results[$this->persistenceKey];
        } catch (\Exception $e) {
            \common_Logger::e("Error on call " . $function . " : " . $e->getMessage());
        }

    }

    /**
     * Proxy to query
     * @param $statement
     * @param $params
     *
     * @return mixed
     */
    public function query($statement, $params)
    {
        return $this->getValidResultOnCall('query', array($statement, $params));
    }

    /**
     * Proxy to exec
     * @param $statement
     * @param $params
     *
     * @return mixed
     */
    public function exec($statement, $params)
    {
        return $this->getValidResultOnCall('exec', array($statement, $params));
    }

    /**
     * Proxy to insert
     * @param       $tableName
     * @param array $data
     *
     * @return mixed
     */
    public function insert($tableName, array $data)
    {
        return $this->getValidResultOnCall('insert', array($tableName, $data));
    }

    /**
     * Proxy to lastInsertId
     * @param null $name
     *
     * @return mixed
     */
    public function lastInsertId($name = null) {
        return $this->getValidResultOnCall('lastInsertId', array($name));
    }

    /**
     * Proxy to quote
     * @param     $parameter
     * @param int $parameter_type
     *
     * @return mixed
     */
    public function quote($parameter, $parameter_type = PDO::PARAM_STR) {
        return $this->getValidResultOnCall('quote', array($parameter, $parameter_type));
    }

    /**
     * Log persistences results and throw exception
     *
     * @param $result
     * @throws \Exception
     */
    public function error($result)
    {
        \common_Logger::e('Persistence values are not equals');
        \common_Logger::e(print_r($result, true));
        throw new \Exception('Persistence consistency problem.');
    }

    public function getSchemaManager()
    {
        return $this->persistences[$this->persistenceKey]->getSchemaManager();
    }

    public function getPlatForm()
    {
        return $this->persistences[$this->persistenceKey]->getPlatForm();
    }

    public function __destruct()
    {
        echo '******';
    }
}