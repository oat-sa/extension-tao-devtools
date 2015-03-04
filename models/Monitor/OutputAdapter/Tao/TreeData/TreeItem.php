<?php
/**
 * @Author      Antoine Delamarre <antoine.delamarre@vesperiagroup.com>
 * @Date        17/02/15
 * @File        TreeItem.php
 * @Copyright   Copyright (c) Doctena - All rights reserved
 * @Licence     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */

namespace oat\taoDevTools\models\Monitor\OutputAdapter\Tao\TreeData;


class TreeItem {

    protected $score = 0.0;

    protected $data;

    protected $attributes = array('class' =>  'node-instance');

    public function __construct($id, $data, $score = 0) {
        $this->attributes['id'] = $id;
        $this->attributes['data-uri'] = $id;
        $this->data = $data;
    }

    public function getId() {
        return $this->attributes['id'];
    }

    public function toArray() {
        return array(
            'data' => ($this->score) ? $this->score . ' - ' . $this->data: $this->data,
            'attributes' => $this->attributes
        );
    }

    public function getScore() {
        return $this->score;
    }
}