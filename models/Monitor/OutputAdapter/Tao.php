<?php
/**
 * @Author      Antoine Delamarre <antoine.delamarre@vesperiagroup.com>
 * @Date        10/02/15
 * @File        Tao.php
 * @Copyright   Copyright (c) Doctena - All rights reserved
 * @Licence     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */

namespace oat\taoDevTools\models\Monitor\OutputAdapter;


use oat\taoDevTools\models\Monitor\Chunk\RequestChunk;
use oat\taoDevTools\models\Monitor\OutputAdapter\Tao\KeyValueStorage;
use oat\taoDevTools\models\Monitor\OutputAdapter\Tao\StorageAdapter;

class Tao extends AbstractAdapter {

    /**
     * persistence configuration key name
     */
    const PERSISTENCE_ID = 'monitor';

    /**
     * Chunk cache
     * @var array
     */
    protected $chunkCache = array();

    /**
     * @var bool
     */
    protected $writeOnlyDuplicated = false;

    /**
     * @var StorageAdapter
     */
    protected $storage;

    /**
     * Initialisation
     */
    public function init() {
        $this->storage = new KeyValueStorage($this, self::PERSISTENCE_ID);
    }

    /**
     * Called by the monitor at construct time
     *
     * @param RequestChunk $request
     *
     * @return mixed
     */
    public function startMonitoring(RequestChunk $request) {
        // TODO: Implement startMonitoring() method.
    }

    /**
     * Called by the monitor at destruct time
     *
     * @param RequestChunk $request
     *
     * @return mixed
     */
    public function endMonitoring(RequestChunk $request) {

        if($this->writeOnlyDuplicated && !count($request->getDuplicatedCalls())) {
            return;
        }

        $this->storage->appendRequest($request);
    }

    /**
     * Erase all data
     */
    public function clearData() {
        $this->storage->purge();
    }

    /**
     * Retrieve a chunk by its id
     * @param $key
     *
     * @return mixed
     */
    public function getChunk($id) {
        return $this->storage->loadChunk($id);
    }

    /**
     * Return a json array to the tao tree
     */
    public function getTreeData($key) {
        return $this->storage->getTaoTreeData($key);
    }

} 