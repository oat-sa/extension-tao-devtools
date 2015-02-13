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


use oat\taoDevTools\models\Monitor\Chunk\ClassChunk;
use oat\taoDevTools\models\Monitor\Chunk\MethodChunk;
use oat\taoDevTools\models\Monitor\Chunk\RequestChunk;
use oat\taoDevTools\models\Monitor\Monitor;

class Tao extends AbstractAdapter {

    
    const PERSISTENCE_ID = 'monitor';
    
    const PERSISTENCE_KEY = 'monitor';

    const KEY_SEPARATOR = '-';

    const REQUEST_KEY = 'r';

    const CLASS_KEY = 'c';

    const METHOD_KEY = 'm';

    protected $persistence;
    /**
     * Initialisation
     */
    public function init() {
        $this->persistence = \common_persistence_Manager::getPersistence(self::PERSISTENCE_ID);
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

        $this->updateRequestIndex($request);
    }

    /**
     * @param array $parameters
     * @param       $finalSeparator
     *
     * @return string
     */
    public function generateKey(array $parameters, $finalSeparator = false) {
        array_unshift($parameters, self::PERSISTENCE_KEY);
        return implode(self::KEY_SEPARATOR, $parameters) . ($finalSeparator ? self::KEY_SEPARATOR : '');
    }

    public function generateObjectKey($request, $class = null, $method = null) {
        $parameters = array(self::REQUEST_KEY, $request instanceof RequestChunk ? $request->getId() : $request);
        if($class) {
            $parameters[] = self::CLASS_KEY;
            $parameters[] = $class instanceof classChunk ? $class->getId() : $class;
        }
        if($method) {
            $parameters[] = self::METHOD_KEY;
            $parameters[] = $method instanceof methodChunk ? $method->getId() : $method;
        }
            
        return $this->generateKey($parameters);
    }
    /**
     * @param RequestChunk $request
     * @param              $id
     * @param              $persistence
     */
    protected function updateClassIndex(RequestChunk $request) {

        $classIndex = $this->getClassIndex($request);

        foreach($request->getClasses() as $name => $class) {

                $classIndex[$name] = array(
                    'methods'    => array_keys($class->getMethods()),
                    'calls'      => count($class->getCalls()),
                    'duplicated' => count($class->getDuplicatedCalls()),
                    'id'         => $class->getId()
                );

                $this->updateMethodIndex($request, $class);
                $this->setClass($class);
        }

        $this->setClassIndex($request, $classIndex);


    }

    /**
     * @param RequestChunk $request
     * @param              $id
     * @param              $persistence
     */
    protected function updateMethodIndex($request, $class) {


        $methodIndex = $this->getMethodIndex($request, $class);

        foreach($class->getMethods() as $name => $method) {

            $methodIndex[$name] = array(
                'calls'      => count($method->getCalls()),
                'duplicated' => count($method->getDuplicatedCalls()),
                'id'         => $method->getId()
            );

            $this->setMethod($class, $method);
        }

        $this->setMethodIndex($class, $methodIndex);

    }

    /**
     * @param RequestChunk $request
     */
    protected function updateRequestIndex(RequestChunk $request) {

        $index      = $this->getRequestIndex();

        $index[$request->getId()] = array(
            'classes'    => array_keys($request->getClasses()),
            'calls'      => count($request->getCalls()),
            'duplicated' => count($request->getDuplicatedCalls()),
            'uri'        => $request->getUri(),
            'id'        => $request->getId()
        );

        $this->setRequestIndex($index);

        $this->updateClassIndex($request);

        $this->setRequest($request);
    }

    protected function getRequestIndex() {
        return $this->persistence->get(
            $this->generateKey(array(
                    self::REQUEST_KEY, '__index'
                )
            )
        ) ?: array();
    }

    protected function setRequestIndex($index) {
        $this->persistence->set(
            $this->generateKey(array(
                    self::REQUEST_KEY, '__index'
                )
            ),
            $index
        ) ?: array();
    }

    protected function getRequest($id) {
        return $this->persistence->get(
            $this->generateKey(array(
                    self::REQUEST_KEY, $id
                )
            )
        );
    }

    protected function setRequest(RequestChunk $request) {

        $this->persistence->set(
            $this->generateKey(array(
                    self::REQUEST_KEY, $request->getId()
                )
            ),
            $request
        );
    }

    protected function getClassIndex($request) {
        $id = ($request instanceof RequestChunk) ? $request->getId() : $request;
        return $this->persistence->get(
            $this->generateKey(array(
                    self::REQUEST_KEY   , $id,
                    self::CLASS_KEY     , '__index'
                )
            )
        ) ?: array();
    }

    protected function getMethodIndex($request, $class) {
        $requestId = ($request instanceof RequestChunk) ? $request->getId() : $request;
        $classId = ($class instanceof ClassChunk) ? $class->getId() : $class;

        return $this->persistence->get(
            $this->generateKey(array(
                    self::REQUEST_KEY   , $requestId,
                    self::CLASS_KEY     , $classId,
                    self::METHOD_KEY    , '__index'
                )
            )
        ) ?: array();
    }

    protected function setClassIndex(RequestChunk $request, $index) {
        $this->persistence->set(
            $this->generateKey(array(
                    self::REQUEST_KEY   , $request->getId(),
                    self::CLASS_KEY     , '__index'
                )
            ),
            $index
        );
    }

    protected function setMethodIndex(ClassChunk $class, $index) {
        $this->persistence->set(
            $this->generateKey(array(
                    self::REQUEST_KEY   , $class->getRequest()->getId(),
                    self::CLASS_KEY     , $class->getId(),
                    self::METHOD_KEY    , '__index'
                )
            ),
            $index
        );
    }

    protected function setClass(ClassChunk $class) {
        $this->persistence->set(
            $this->generateKey(array(
                    self::REQUEST_KEY   , $class->getRequest()->getId(),
                    self::CLASS_KEY     , $class->getId()
                )
            ),
            $class
        );
    }

    protected function setMethod(ClassChunk $class, MethodChunk $method) {
        $this->persistence->set(
            $this->generateKey(array(
                    self::REQUEST_KEY   , $class->getRequest()->getId(),
                    self::CLASS_KEY     , $class->getId(),
                    self::METHOD_KEY    , $method->getId()
                )
            ),
            $method
        );
    }

    public function getObject($key) {
        return $this->persistence->get($key);
    }

    public function getTreeData() {
        $data = array(
            'data' 	=> 'Requests',
            'attributes' => array(
                'id' => 'root',
                'class' => 'node-class'
            ),
            'children' => array()
        );

        $index = $this->getRequestIndex();
        foreach($index as $id => $request) {
            $requestChild = array(
                'data' 	=> substr($request['uri'], 0, 60),
                'attributes' => array(
                    'id' => $this->generateObjectKey($id),
                    'class' => 'node-class'
                ),
                'children' => array()
            );

            $classIndex = $this->getClassIndex($request['id']);
            foreach($classIndex as $className => $class) {
                
                $explodedClassName = explode('\\', $className);
                $classChild = array(
                    'data' 	=> array_pop($explodedClassName),
                    'attributes' => array(
                        'id' => $this->generateObjectKey($id,$class['id']),
                        'class' => 'node-class'
                    ),
                    'children' => array()
                );

                $methodIndex = $this->getMethodIndex($request['id'], $class['id']);
                foreach($methodIndex as $methodName => $method) {
                    $explodedMethodName = explode('::', $methodName);
                    $methodChild = array(
                        'data' 	=> array_pop($explodedMethodName),
                        'attributes' => array(
                            'id' => $this->generateObjectKey($id, $class['id'], $method['id']),
                            'class' => 'node-class'
                        ),
                        'children' => array()
                    );
                    $classChild['children'][] = $methodChild;
                }
                $requestChild['children'][] = $classChild;
            }
            $data['children'][] = $requestChild;
        }

        echo json_encode($data);
    }
} 