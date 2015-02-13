<?php
/**
 * CandidatSkills (http://www.candidatskills.com/)
 *
 * @copyright Copyright (c) 2013-2014 CandidatSkills (http://www.candidatskills.com)
 * @license   http://www.candidatskills/license
 */

namespace oat\taoDevTools\models\Monitor\Chunk\Call;

use oat\taoDevTools\models\Monitor\Chunk\CallChunk;

abstract class AbstractContainer
{


    protected $calls = [];

    protected $duplicatedCalls = [];

    /**
     * @var AbstractContainer
     */
    protected $parentChunk;

    /**
     * Return the linked parent chunk
     * @return common_monitor_AbstractChunkCallContainer
     */
    public function getParentChunk() {
        return $this->parentChunk;
    }

    /**
     * @param $call
     */

    /**
     * Propagate a call in the tree
     * @param common_monitor_Chunk_Call $call
     * @param bool                   $recursive
     */
    public function addCall(CallChunk $call, $recursive = true) {

        $this->calls[] = $call;

        if($call->isDuplicated()) {
            $this->duplicatedCalls[] = $call;
        }


        if($recursive) {

            $parent = $this->getParentChunk();

            if($parent) {
                $parent->addCall($call);
            }
        }
    }

    /**
     * Propagate the a duplicated call in the tree
     * @param common_monitor_Chunk_Call $call
     * @param bool                   $recursive
     */
    public function addDuplicatedCall(CallChunk $call, $recursive = true) {
        $this->duplicatedCalls[] = $call;
        if($recursive) {

            $parent = $this->getParentChunk();

            if($parent) {
                $parent->addDuplicatedCall($call);
            }
        }
    }

    /**
     * @return array
     */
    public function getCalls() {
        return $this->calls;
    }

    /**
     * @return array
     */
    public function getDuplicatedCalls() {
        return $this->duplicatedCalls;
    }



}