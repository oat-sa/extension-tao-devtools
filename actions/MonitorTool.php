<?php
/**  
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
namespace oat\taoDevTools\actions;

use oat\controllerMap\parser\Factory;
use oat\taoDevTools\models\Monitor\Exception\AdapterNotFound;
use oat\taoDevTools\models\Monitor\Exception\ChunkNotFoundException;
use oat\taoDevTools\models\Monitor\Monitor;
use oat\taoDevTools\models\Monitor\OutputAdapter\KeyValueAdapter;

/**
 * Extensions management controller
 *
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package tao
 * @subpackage actions
 *
 */
class MonitorTool extends \tao_actions_CommonModule {

    const KEY_NAMESPACE = "generis:monitor:";

    public function __construct() {

    }


    /**
     * @example method used to populate the tree widget
     * render json data of the documents in the DOCS_PATH
     * @return void
     */
    public function getTreeData(){
        return Monitor::getInstance()->getAdapter('tao')
            ->getTreeData($this->getRequestParameter('classUri'));
    }

    public function index() {
        $config = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDevTools')->getConfig('monitor');

        $this->setData('proxyPersistenceMap', isset($config['proxyPersistenceMap']) ? $config['proxyPersistenceMap'] : array() );
        $this->setData('adapters', isset($config['adapters']) ? $config['adapters'] : array() );

        $this->setView('monitorTool/index.tpl');
    }

    /**
     * Dispatch action in regard of the selected node item
     * (Called by the tree)
     * @throws \oat\taoDevTools\models\Monitor\Exception\ChunkNotFoundException
     * @throws AdapterNotFoundException
     */
    public function dispatch() {
        if(!$storageKey = $this->getRequestParameter('uri')) {
            $storageKey = $this->getRequestParameter('classUri');
        }

        if($storageKey == 'root') {
            $this->forward('index');
        } else {
            $taoAdapter = Monitor::getInstance()->getAdapter('tao');
            if(!$taoAdapter) {
                throw new AdapterNotFoundException('You must activate the tao adapter to enable reporting in the tao interface.');
            }
            $chunk = $taoAdapter->getChunk($storageKey);
            if($chunk === false) {
                throw new ChunkNotFoundException('Chunk not found with key :' . $storageKey);
            }
            $className = get_class($chunk);
            $explodedClassName = explode('\\', $className);
            $className = array_pop($explodedClassName);
            $this->forward('show' . $className, null, null, array('classUri' => $storageKey));

        }
    }

    /**
     * Root tree item property page
     */
    public function showRoot() {
        echo 'root';
    }

    /**
     * Request tree item property page
     */
    public function showRequestChunk() {

        $classUri = $this->getRequestParameter('classUri');
        $request = Monitor::getInstance()->getAdapter('tao')->getChunk($classUri);
        $this->setData('adapter', Monitor::getInstance()->getAdapter('tao'));
        $this->setData('request', $request);
        $this->setView('monitorTool/request.tpl');


    }

    /**
     * Class tree item property page
     */
    public function showClassChunk() {

        $classUri = $this->getRequestParameter('classUri');
        $class = Monitor::getInstance()->getAdapter('tao')->getChunk($classUri);
        $this->setData('class', $class);
        $this->setData('adapter', Monitor::getInstance()->getAdapter('tao'));
        $this->setView('monitorTool/class.tpl');

    }

    /**
     * Method tree item property page
     */
    public function showMethodChunk() {

        $classUri = $this->getRequestParameter('classUri');
        $class = Monitor::getInstance()->getAdapter('tao')->getChunk($classUri);
        $this->setData('method', $class);
        $this->setData('adapter', Monitor::getInstance()->getAdapter('tao'));
        $this->setView('monitorTool/method.tpl');

    }

    /**
     * Method tree item property page
     */
    public function showCallGroupChunk() {

        $classUri = $this->getRequestParameter('uri');
        $adapter = Monitor::getInstance()->getAdapter('tao');
        $callGroup = $adapter->getChunk($classUri);
        $this->setView('monitorTool/call-group.tpl');
        $this->setData('adapter', Monitor::getInstance()->getAdapter('tao'));

        $calls = $callGroup->getCalls();

        $this->setData('mergedTraceInfo', $adapter->getMergedTraceInfo($calls));
        $this->setData('adapter', $adapter);

    }

    //XHR

    /**
     * Start the monitor
     */
    public function startMonitor() {
        if($config = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDevTools')->getConfig('monitor')) {
            $config['enabled'] = true;
            \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDevTools')->setConfig('monitor', $config);
        }

        $status = Monitor::getInstance()->isEnabled();
        $this->returnJson(array(
                'success' => $status,
                'status' => $status
            )
        );
    }

    /**
     * Stop the monitor
     */
    public function stopMonitor() {
        if($config = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDevTools')->getConfig('monitor')) {
            $config['enabled'] = false;
            \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDevTools')->setConfig('monitor', $config);
        }
        $status = Monitor::getInstance()->isEnabled();
        $this->returnJson(array(
                'success' => !$status,
                'status' => $status
            )
        );
    }

    /**
     * Clear monitor data
     */
    public function clearMonitor() {

        Monitor::getInstance()->getAdapter('tao')->clearData();
        $this->returnJson(array(
                'success' => true,
                'status' => $status = Monitor::getInstance()->isEnabled(),
                'refresh' => true
            )
        );
    }

    /**
     * Return the monitor status
     */
    public function getMonitorStatus() {

        $status = Monitor::getInstance()->isEnabled();

        $this->returnJson(array(
                'success' => true,
                'message' => 'Monitor is ' . ($status),
                'status' => Monitor::getInstance()->isEnabled()
            )
        );
    }


}