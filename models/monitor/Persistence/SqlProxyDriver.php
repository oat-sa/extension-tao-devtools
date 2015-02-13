<?php
/**
 * @Author      Antoine Delamarre <antoine.delamarre@vesperiagroup.com>
 * @Date        10/02/15
 * @File        ProxyDriver.php
 * @Copyright   Copyright (c) Doctena - All rights reserved
 * @Licence     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */

namespace oat\taoDevTools\models\Monitor\Persistence;


use common_persistence_Persistence;
use oat\taoDevTools\models\Monitor\Monitor;
use PDO;

class SqlProxyDriver implements \common_persistence_sql_Driver{

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var string
     */
    protected $id;

    /**
     * @var \common_persistence_sql_Driver
     */
    protected $persistence;

    /**
     * Allow to connect the driver and return the connection
     *
     * @param string $id
     * @param array  $params
     *
     * @return \common_persistence_Persistence
     */
    function connect($id, array $params) {

        $this->id = $id;
        $this->config = $params;

        $this->persistence = \common_persistence_Manager::getPersistence($params['persistenceId']);

        unset($params['persistenceId']);

        return new \common_persistence_SqlPersistence($params, $this);
    }

    /**
     * Proxy to query
     * @param $statement
     * @param $params
     *
     * @return mixed
     */
    public function query($statement, $params) {

        $call = Monitor::getInstance()->append($this, __METHOD__, func_get_args());
        $call->startTiming();
        $result = $this->persistence->query($statement, $params);
        $call->stopTiming();
        return $result;

    }

    /**
     * Proxy to exec
     * @param $statement
     * @param $params
     *
     * @return mixed
     */
    public function exec($statement, $params) {
        $call = Monitor::getInstance()->append($this, __METHOD__, func_get_args());
        $call->startTiming();
        $result = $this->persistence->exec($statement, $params);
        $call->stopTiming();
        return $result;
    }

    /**
     * Proxy to insert
     * @param       $tableName
     * @param array $data
     *
     * @return mixed
     */
    public function insert($tableName, array $data) {
        $call = Monitor::getInstance()->append($this, __METHOD__, func_get_args());
        $call->startTiming();
        $result = $this->persistence->insert($tableName, $data);
        $call->stopTiming();
        return $result;
    }

    /**
     * Proxy to getSchemaManager
     * @return mixed
     */
    public function getSchemaManager() {
        return $this->persistence->getSchemaManager();
    }

    /**
     * Proxy to getPlatForm
     * @return mixed
     */
    public function getPlatForm() {
        return $this->persistence->getPlatForm();
    }

    /**
     * Proxy to lastInsertId
     * @param null $name
     *
     * @return mixed
     */
    public function lastInsertId($name = null) {
        return $this->persistence->lastInsertId($name);
    }

    /**
     * Proxy to quote
     * @param     $parameter
     * @param int $parameter_type
     *
     * @return mixed
     */
    public function quote($parameter, $parameter_type = PDO::PARAM_STR) {
        return $this->persistence->quote($parameter, $parameter_type);
    }
}