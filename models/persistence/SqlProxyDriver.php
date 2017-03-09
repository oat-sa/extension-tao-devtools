<?php
/**
 * @Author      Antoine Delamarre <antoine.delamarre@vesperiagroup.com>
 * @Date        10/02/15
 * @File        ProxyDriver.php
 * @Copyright   Copyright (c) Doctena - All rights reserved
 * @Licence     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */

namespace oat\taoDevTools\models\persistence;

use common_Logger;
use PDO;
use PDOException;

class SqlProxyDriver implements \common_persistence_sql_Driver{

    private $count = 0;
    
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
    public function connect($id, array $params) {

        $this->id = $id;

        $this->persistence = \common_persistence_SqlPersistence::getPersistence($params['persistenceId']);

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
        $this->count++;
        try {
            return $this->persistence->query($statement, $params);
        } catch (PDOException $e) {
            common_Logger::w('Failed: '.$statement);
            throw $e;
        }
    }

    /**
     * Proxy to exec
     * @param mixed $statement
     * @param array $params
     *
     * @param array $types
     * @return mixed
     * @throws \Exception
     */
    public function exec($statement, array $params, array $types) {
        $this->count++;
        try {
            return $this->persistence->exec($statement, $params, $types);
        } catch (PDOException $e) {
            common_Logger::w('Failed: '.$statement);
            throw $e;
        }
    }

    /**
     * Proxy to insert
     * @param string $tableName
     * @param array $data
     *
     * @return mixed
     */
    public function insert($tableName, array $data) {
        $this->count++;
        try {
            return $this->persistence->insert($tableName, $data);
        } catch (PDOException $e) {
            common_Logger::w('Failed: '.$tableName);
            throw $e;
        }
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
    
    public function __destruct()
    {
        common_Logger::i($this->count.' queries to '.$this->id);
    }
}