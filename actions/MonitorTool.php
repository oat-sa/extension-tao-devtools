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

    public function index() {
        $config = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDevTools')->getConfig('monitor');

        $this->setData('proxyPersistenceMap', isset($config['proxyPersistenceMap']) ? $config['proxyPersistenceMap'] : array() );

        $this->setData('enabled', Monitor::getInstance()->isEnabled());

        $this->setView('monitorTool/index.tpl');
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

    public function showClassProperty() {
        //echo(print_r($this->getRequestParameters(), true));

        $classUri = $this->getRequestParameter('classUri');

        if($classUri == 'root') {
            $this->forward('index');
        } else {
            $class = Monitor::getInstance()->getAdapter('tao')->getObject($classUri);
            switch(get_class($class)) {
                case 'oat\taoDevTools\models\Monitor\Chunk\RequestChunk':

                case 'oat\taoDevTools\models\Monitor\Chunk\ClassChunk':

                case 'oat\taoDevTools\models\Monitor\Chunk\MethodChunk':
            }

        }
    }

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

    public function showProperty() {
        echo(print_r($this->getRequestParameters(), true));
    }


    /**
     * @example method used to populate the tree widget
     * render json data of the documents in the DOCS_PATH
     * @return void
     */
    public function getTreeData(){

        return Monitor::getInstance()->getAdapter('tao')
            ->getTreeData();


    }

    /**
     * Change the default generis configuration
     * to use the proxy and start monitoring calls
     */
    public function installProxy() {
        Monitor::getInstance()->installProxy();
    }

    /**
     * Revert the configuration to it's previous state
     * and stop monitoring calls
     */
    public function uninstallProxy() {
        Monitor::getInstance()->uninstallProxy();
    }

    /**
     * this function must contain the word edit
     */
    public function editDocument(){
        $filepath = $this->getRequestParameter('uri');

        // send data to the template
        $this->setData('filename', substr($filepath, strrpos($filepath, '/')+1));
        $this->setData('downloadpath', DOCS_URL.$filepath);

        // select the template
        $this->setView('editDocument.tpl');
    }

    /**
     * @see TaoModule::getRootClass
     * @abstract implement the abstract method
     */
    public function getRootClass(){
        return null;
    }
}