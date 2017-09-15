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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\taoDevTools\scripts;

use oat\tao\model\TaoOntology;
use oat\tao\scripts\update\OntologyUpdater;
use oat\tao\model\accessControl\func\AclProxy;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use oat\oatbox\action\Action;
use oat\generis\model\OntologyAwareTrait;
use oat\tao\model\accessControl\func\AccessRule;

/**
 * Restores a minimal viable model 1
 * 
 * @author joel.bout
 */
class RestoreModelOne implements Action, ServiceLocatorAwareInterface
{

    use OntologyAwareTrait;
    use ServiceLocatorAwareTrait;
    
    public function __invoke($params) {
        
        // recreate languages
        $modelCreator = new \tao_install_utils_ModelCreator(LOCAL_NAMESPACE);
        $models = $modelCreator->getLanguageModels();
        foreach ($models as $ns => $modelFiles){
            foreach ($modelFiles as $file){
                $modelCreator->insertLocalModel($file);
            }
        }
        
        OntologyUpdater::syncModels();
        
        // reapply access rights
        $exts = \common_ext_ExtensionsManager::singleton()->getInstalledExtensions();
        foreach ($exts as $ext) {
            $installer = new \tao_install_ExtensionInstaller($ext);
            $installer->installManagementRole();
            $installer->applyAccessRules();
        }
        
        // recreate admin
        if (count($params) >= 2) {
            $login = array_shift($params);
            $password = array_shift($params);
            $sysAdmin = $this->getResource(TaoOntology::INSTANCE_ROLE_SYSADMIN);
            $userClass = $this->getClass(TaoOntology::CLASS_TAO_USER);
            \core_kernel_users_Service::singleton()->addUser($login, $password, $sysAdmin, $userClass);
        }
        
        // empty cache
        \common_cache_FileCache::singleton()->purge();
        
        return \common_report_Report::createSuccess('All done');
    }
}