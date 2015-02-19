<?php
/**
 * @Author      Antoine Delamarre <antoine.delamarre@vesperiagroup.com>
 * @Date        18/02/15
 * @File        CallGroupChunk.php
 * @Copyright   Copyright (c) Doctena - All rights reserved
 * @Licence     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */

namespace oat\taoDevTools\models\Monitor\OutputAdapter\Tao;


use oat\taoDevTools\models\Monitor\Chunk\MethodChunk;

class CallGroupChunk {

    protected $calls;

    protected $method;

    protected $id;

    public function __construct($id, MethodChunk $method, array $calls) {
        $this->id = $id;
        $this->method = $method;
        $this->calls = $calls;

    }

    public function getId() {

        return $this->id;
    }

    public function getCalls() {
        return $this->calls;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getScore() {
        return (int) count($this->calls);
    }
} 