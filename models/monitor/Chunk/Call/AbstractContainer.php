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
    /**
     * @var CallChunk[]
     */
    protected $calls = [];

    /**
     * @var CallChunk[]
     */
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
     * Propagate a call in the tree
     * @param CallChunk $call
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

    /**
     * Return score as Calls / DuplicatedCalls
     * @return int|string
     */
    public function getScore() {
        $nbrCalls           = (float) count($this->getCalls());
        $nbrDuplicatedCalls = (float) count($this->getDuplicatedCalls());
        return ($nbrDuplicatedCalls ? sprintf('%.2f', ((100 / $nbrCalls) * $nbrDuplicatedCalls)) : 0);
    }

}