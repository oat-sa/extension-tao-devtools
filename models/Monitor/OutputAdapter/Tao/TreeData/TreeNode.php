<?php
/**
 * @Author      Antoine Delamarre <antoine.delamarre@vesperiagroup.com>
 * @Date        17/02/15
 * @File        TreeNode.php
 * @Copyright   Copyright (c) Doctena - All rights reserved
 * @Licence     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */

namespace oat\taoDevTools\models\Monitor\OutputAdapter\Tao\TreeData;


class TreeNode extends TreeItem
{

    const CLOSED = 'closed';
    const OPEN = 'open';

    protected $count;

    protected $state = self::CLOSED;

    protected $attributes = array('class' =>  'node-class');

    protected $children = [];

    public function __construct($id, $data, $score = 0, $state = self::CLOSED) {
        parent::__construct($id, $data);
        $this->children = new TreeItemCollection();
        $this->score = $score;
        $this->state = $state;
    }

    public function addChildren(TreeItem $children) {

        $this->children[$children->getId()] = $children;

    }

    public function toArray() {

        $result = parent::toArray();

        if(is_null($this->count)) {

            $count = count($this->children);
            if($count) {
                $result['count'] = count($this->children);
                $result['children'] = $this->children->toArray();
            }

        } else {
            $result['count'] = $this->count;

        }

        if(isset($result['count']) && $result['count']) {
            $result['state'] = $this->state;
        }


        return $result;
    }

    /**
     * @param mixed $count
     *
     * @return TreeNode
     */
    public function setCount($count) {
        $this->count = $count;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCount() {
        return $this->count;
    }


} 