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

class AdvKeyValueProxyDriver implements \common_persistence_AdvKvDriver
{
    /**
     * @var integer
     */
    protected $count = 0;

    /**
     * @var integer
     */
    protected $seeks = 0;
    
    /**
     * @var \common_persistence_AdvKvDriver
     */
    protected $persistence;

    /**
     * @var bool
     */
    protected $detailedLogging;

    /**
     * @var string
     */
    protected $id;

    function connect($id, array $params)
    {
        $this->id = $id;
        $this->persistence = \common_persistence_AdvKeyValuePersistence::getPersistence($params['persistenceId']);
        $this->detailedLogging = isset($params['details']) ? (bool)$params['details'] : false;

        unset($params['persistenceId']);
        unset($params['details']);
        return new \common_persistence_AdvKeyValuePersistence($params, $this);
    }

    /**
     * Log the number of requests to the keyvalue persistence
     */
    public function __destruct()
    {
        \common_Logger::i($this->count . ' calls to ' . $this->id);
        if ($this->seeks > 0) {
            \common_Logger::w($this->seeks . ' seeks in ' . $this->id);
        }
    }
    
    protected function log($call, $key)
    {
        $this->count++;
        if ($this->detailedLogging) {
            \common_Logger::d('Call for ' . $key . ' (' . $call . ')');
        }
    }

    /**
     * {@inheritDoc}
     * @see \common_persistence_KvDriver::set()
     */
    public function set($key, $value, $ttl = null, $nx = false)
    {
        $this->log(__FUNCTION__, $key);
        return $this->persistence->set($key, $value, $ttl, $nx);
    }

    /**
     * {@inheritDoc}
     * @see \common_persistence_AdvKvDriver::incr()
     */
    public function incr($key)
    {
        $this->log(__FUNCTION__, $key);
        return $this->persistence->incr($key);
    }

    /**
     * {@inheritDoc}
     * @see \common_persistence_AdvKvDriver::hGetAll()
     */
    public function hGetAll($key)
    {
        $this->log(__FUNCTION__, $key);
        return $this->persistence->hGetAll($key);
    }

    /**
     * {@inheritDoc}
     * @see \common_persistence_AdvKvDriver::keys()
     */
    public function keys($pattern)
    {
        $this->seeks++;
        if ($this->detailedLogging) {
            \common_Logger::w('Call for ' . $key . ' (' . $call . ')');
        }
        return $this->persistence->keys($pattern);
    }

    /**
     * {@inheritDoc}
     * @see \common_persistence_AdvKvDriver::hSet()
     */
    public function hSet($key, $field, $value)
    {
        $this->log(__FUNCTION__, $key);
        return $this->persistence->hSet($key, $field, $value);
    }

    /**
     * {@inheritDoc}
     * @see \common_persistence_KvDriver::del()
     */
    public function del($key)
    {
        $this->log(__FUNCTION__, $key);
        return $this->persistence->del($key);
    }

    /**
     * {@inheritDoc}
     * @see \common_persistence_AdvKvDriver::hmSet()
     */
    public function hmSet($key, $fields)
    {
        $this->log(__FUNCTION__, $key);
        return $this->persistence->hmSet($key, $fields);
    }

    /**
     * {@inheritDoc}
     * @see \common_persistence_AdvKvDriver::hExists()
     */
    public function hExists($key, $field)
    {
        $this->log(__FUNCTION__, $key);
        return $this->persistence->hExists($key, $field);
    }

    /**
     * {@inheritDoc}
     * @see \common_persistence_KvDriver::get()
     */
    public function get($key)
    {
        $this->log(__FUNCTION__, $key);
        return $this->persistence->get($key);
    }

    /**
     * {@inheritDoc}
     * @see \common_persistence_KvDriver::exists()
     */
    public function exists($key)
    {
        $this->log(__FUNCTION__, $key);
        return $this->persistence->exists($key);
    }

    /**
     * {@inheritDoc}
     * @see \common_persistence_AdvKvDriver::hGet()
     */
    public function hGet($key, $field)
    {
        $this->log(__FUNCTION__, $key);
        return $this->persistence->hGet($key, $field);
    }

    /**
     * {@inheritDoc}
     * @see \common_persistence_AdvKvDriver::decr()
     */
    public function decr($key)
    {
        $this->log(__FUNCTION__, $key);
        return $this->persistence->decr($key);
    }
}
