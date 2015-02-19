<?php
/**
 * @Author      Antoine Delamarre <antoine.delamarre@vesperiagroup.com>
 * @Date        17/02/15
 * @File        StorageAdapter.php
 * @Copyright   Copyright (c) Doctena - All rights reserved
 * @Licence     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */

namespace oat\taoDevTools\models\Monitor\OutputAdapter\Tao;


use oat\taoDevTools\models\Monitor\Chunk\RequestChunk;

interface StorageAdapter {

    /**
     * Append a new request to the store
     * @param RequestChunk $request
     *
     * @return mixed
     */
    public function appendRequest(RequestChunk $request);

    /**
     * Return the json representation of the tao tree data structure
     * @param $key This is the class id you specify in the tree structure
     *
     * @return string json
     */
    public function getTaoTreeData($key);

    /**
     * Purge all data
     * @return mixed
     */
    public function purge();
} 