<?php
/**
 * @Author      Antoine Delamarre <antoine.delamarre@vesperiagroup.com>
 * @Date        17/02/15
 * @File        KeyValueStorage.php
 * @Copyright   Copyright (c) Doctena - All rights reserved
 * @Licence     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */

namespace oat\taoDevTools\models\Monitor\OutputAdapter\Tao;


use oat\taoDevTools\models\Monitor\Chunk\ClassChunk;
use oat\taoDevTools\models\Monitor\Chunk\MethodChunk;
use oat\taoDevTools\models\Monitor\Chunk\RequestChunk;
use oat\taoDevTools\models\Monitor\OutputAdapter\AbstractAdapter;
use oat\taoDevTools\models\Monitor\OutputAdapter\Tao\TreeData\TreeItem;
use oat\taoDevTools\models\Monitor\OutputAdapter\Tao\TreeData\TreeItemCollection;
use oat\taoDevTools\models\Monitor\OutputAdapter\Tao\TreeData\TreeNode;
use oat\taoDevTools\models\Monitor\TreeData;

class KeyValueStorage implements StorageAdapter
{

    const PERSISTENCE_KEY = 'monitor';

    const KEY_SEPARATOR = '-';

    const REQUEST_KEY = 'r';

    const CLASS_KEY = 'c';

    const METHOD_KEY = 'm';

    const CALL_GROUP_KEY = 'g';

    /**
     * @var \common_persistence_Persistence
     */
    protected $persistence;

    /**
     * @var AbstractAdapter
     */
    protected $adapter;

    /**
     * Constructor
     * @param string $persistenceId
     */
    public function __construct(AbstractAdapter $adapter, $persistenceId) {
        $this->adapter = $adapter;
        $this->persistence = \common_persistence_Manager::getPersistence($persistenceId);
    }


    /**
     * @param RequestChunk $request
     */
    public function appendRequest(RequestChunk $request) {

        $this->addChild(false, $request);

    }


    /**
     * Purge all data
     * @return mixed
     */
    public function purge() {
        $this->persistence->purge();
    }

    /**
     * Return the json representation of the tao tree data structure
     *
     * @param $key This is the class id you specify in the tree structure
     *
     * @return string json
     */
    public function getTaoTreeData($key) {


        $treeData = new TreeItemCollection();

        $chunk = $this->loadChunk($key);
        $index = $this->loadChildrenIndex($chunk);

        switch(true) {
            case ($chunk === false):

                $rootNode = new TreeNode('root', 'Requests');
                foreach($index as $request) {
                    $node = new TreeNode($request['key'], substr($request['uri'], 0, 60), $request['score']);
                    $node->setCount($request['classes']);
                    $rootNode->addChildren($node);
                }
                $treeData[] = $rootNode;

                break;
            case ($chunk instanceof RequestChunk):

                foreach($index as $className => $class) {
                    if(!$class['score']) {
                        continue;
                    }
                    $explodedClassName = explode('\\', $className);
                    $classNode = new TreeNode($class['key'],  array_pop($explodedClassName), $class['score']);
                    $classNode->setCount($class['methods']);
                    $treeData[$className] = $classNode;
                }
                break;
            case ($chunk instanceof ClassChunk):

                foreach($index as $methodName => $method) {
                    if(!$method['score']) {
                        continue;
                    }
                    $explodedMethodName = explode('::', $methodName);
                    $methodNode = new TreeNode($method['key'], array_pop($explodedMethodName), $method['score']);
                    $methodNode->setCount($method['callGroups']);
                    $treeData[$methodName] = $methodNode;
                }

                break;
            case ($chunk instanceof MethodChunk):

                $count = 1;
                foreach($index as $id => $callGroup) {
                    $treeData[$id] = new TreeItem($callGroup['key'], '#' . $count++ . ' X ' . $callGroup['score'], $callGroup['score']);
                }
                break;
        }

        echo json_encode($treeData->toArray());
    }

    /**
     * Generate a storage key from an array
     * @param array $parameters
     * @param       $finalSeparator
     *
     * @return string
     */
    protected function generateKey(array $parameters, $finalSeparator = false) {
        array_unshift($parameters, self::PERSISTENCE_KEY);
        return implode(self::KEY_SEPARATOR, $parameters) . ($finalSeparator ? self::KEY_SEPARATOR : '');
    }

    /**
     * Link a child to a chunk
     * if chunk == false, link child to the root node
     * @param mixed $chunk Chunk which link the child
     * @param mixed $child Child that will be linked to the chunk
     */
    protected function addChild($chunk = false, $child) {

        $recursiveChildren = array();
        $index = $this->loadChildrenIndex($chunk);

        switch(true) {
            case ($chunk === false):
                $request = $child;
                $index[$request->getId()] = array(
                    'score'         => $request->getScore(),
                    'classes'       => count($request->getClasses()),
                    'calls'         => count($request->getCalls()),
                    'duplicated'    => count($request->getDuplicatedCalls()),
                    'uri'           => $request->getUri(),
                    'id'            => $request->getId(),
                    'key'           => $this->getChunkKey($request)
                );
                $recursiveChildren = $request->getClasses();
                break;

            case ($chunk instanceof RequestChunk) :
                $class = $child;
                $index[$class->getClassName()] = array(
                    'score'     => $class->getScore(),
                    'methods'    => count($class->getMethods()),
                    'calls'      => count($class->getCalls()),
                    'duplicated' => count($class->getDuplicatedCalls()),
                    'id'         => $class->getId(),
                    'key'        => $this->getChunkKey($class)
                );
                $recursiveChildren = $class->getMethods();
                break;

            case ($chunk instanceof ClassChunk) :
                $method = $child;
                $callGroups = $this->callGroupFactory($method);

                $index[$method->getMethodName()] = array(
                    'score'         => $method->getScore(),
                    'callGroups'    => count($callGroups),
                    'calls'         => count($method->getCalls()),
                    'duplicated'    => count($method->getDuplicatedCalls()),
                    'id'            => $method->getId(),
                    'key'           => $this->getChunkKey($method)
                );
                $recursiveChildren = $callGroups;
                break;

            case ($chunk instanceof MethodChunk) :
                $callGroup = $child;
                $index[$callGroup->getId()] = array(
                    'score' => $callGroup->getScore(),
                    'key' => $this->getChunkKey($callGroup)
                );
                $this->saveChunk($child);
                break;
        }


        $this->saveChildrenIndex($chunk, $index);
        $this->saveChunk($chunk);

        foreach($recursiveChildren as $recursiveChild) {
            $this->addChild($child, $recursiveChild);
        }

    }

    /**
     * GroupCallChunk factory
     * This is a virtual chunk to old the merged duplicated call of a method
     * @param MethodChunk $method
     * @param array       $callGroups
     *
     * @return array
     */
    protected function callGroupFactory(MethodChunk $method) {
        $result = array();
        $callGroups = $this->adapter->getMergedDuplicatedCalls($method->getDuplicatedCalls());
        foreach($callGroups as $hash => $callGroup) {
            $callGroupChunk = new CallGroupChunk($hash, $method, $callGroup);
            $result[] = $callGroupChunk;
        }
        return $result;
    }

    /**
     * Return a storage key for a chunk or for chunk collection
     *
     * @param bool $chunk
     * @param bool $collection
     *
     * @return string
     */
    protected function getChunkKey($chunk = false, $collection = false) {
        $key = array();
        switch(true) {
            case ($chunk === false):
                if($collection) {
                    $key = array(self::REQUEST_KEY, '__index');
                }
                break;

            case ($chunk instanceof RequestChunk) :
                $key = array(
                    self::REQUEST_KEY   , $chunk->getId(),
                );
                if($collection) {
                    array_push($key,self::CLASS_KEY, '__index');
                }
                break;

            case ($chunk instanceof ClassChunk) :
                $key = array(
                    self::REQUEST_KEY   , $chunk->getRequest()->getId(),
                    self::CLASS_KEY     , $chunk->getId(),
                );
                if($collection) {
                    array_push($key, self::METHOD_KEY, '__index');
                }
                break;

            case ($chunk instanceof MethodChunk) :
                $class = $chunk->getInstance()->getClass();
                $request = $class->getRequest();
                $key = array(
                    self::REQUEST_KEY       , $request->getId(),
                    self::CLASS_KEY         , $class->getId(),
                    self::METHOD_KEY        , $chunk->getId(),
                );
                if($collection) {
                    array_push($key, self::CALL_GROUP_KEY, '__index');
                }
                break;
            case ($chunk instanceof CallGroupChunk):
                $method = $chunk->getMethod();
                $class = $method->getInstance()->getClass();
                $request = $class->getRequest();
                $key = array(
                    self::REQUEST_KEY       , $request->getId(),
                    self::CLASS_KEY         , $class->getId(),
                    self::METHOD_KEY        , $method->getId(),
                    self::CALL_GROUP_KEY    , $chunk->getId()
                );
                if($collection) {
                    array_push($key, 'unimplemented', '__index');
                }
        }

        return $this->generateKey($key);
    }

    /**
     * Load a Chunk children collection
     * @param bool $chunk
     *
     * @return array
     */
    protected function loadChildrenIndex($chunk = false) {
        return $this->persistence->get($this->getChunkKey($chunk, true)) ?: array();
    }

    /**
     * Save a chunk children collection
     * @param bool $chunk
     * @param      $index
     *
     * @return mixed
     */
    protected function saveChildrenIndex($chunk = false, $index) {
        return $this->persistence->set($this->getChunkKey($chunk, true), $index);
    }

    /**
     * Save a chunk
     * @param $chunk
     */
    protected function saveChunk($chunk) {
        if($chunk) {
            $this->persistence->set($this->getChunkKey($chunk), $chunk);
        }
    }

    /**
     * Load a chunk
     * @param $id
     *
     * @return mixed
     */
    public function loadChunk($id) {
        if(!isset($this->chunkCache[$id])) {
            $this->chunkCache[$id] = $this->persistence->get($id);
        }
        return $this->chunkCache[$id];
    }

}