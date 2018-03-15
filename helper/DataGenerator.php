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
 * Copyright (c) 2013 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 * @license GPLv2
 * @package taoDevTools
 *
 */
namespace oat\taoDevTools\helper;

use oat\tao\model\TaoOntology;
use oat\generis\model\GenerisRdf;
use oat\generis\model\OntologyRdfs;
use oat\taoQtiItem\model\qti\ImportService;
use helpers_TimeOutHelper;
use oat\taoQtiItem\model\qti\Service;
use oat\taoTestTaker\models\TestTakerService;
use oat\taoGroups\models\GroupsService;

class DataGenerator
{

    public static function generateItems($count = 100) {
        // load QTI constants
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoQtiItem');
        
        $ext = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDevTools');
        
        $generationId = NameGenerator::generateRandomString(4);
        
        $topClass = new \core_kernel_classes_Class(TaoOntology::ITEM_CLASS_URI);
        $class = $topClass->createSubClass('Generation '.$generationId);
        $fileClass = new \core_kernel_classes_Class('http://www.tao.lu/Ontologies/generis.rdf#File');
        
        $sampleFile = $ext->getDir().'data/items/sampleItem.xml';
        
        helpers_TimeOutHelper::setTimeOutLimit(helpers_TimeOutHelper::LONG);
        for ($i = 0; $i < $count; $i++) {
        
            $report = ImportService::singleton()->importQTIFile($sampleFile, $class, false);
            $item = $report->getData();
            $item->setLabel(NameGenerator::generateTitle());
        }
        helpers_TimeOutHelper::reset();
        
        return $class;
    }

    /**
     * Generates $count of problematic multilanguage items. The secondary item language version will have
     * modification time a second after the default
     *
     * @param int $count
     * @return array
     */
    public static function generateMultilanguageItems($count = 100)
    {
        $ext = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDevTools');

        $topClass = new \core_kernel_classes_Class(TaoOntology::ITEM_CLASS_URI);
        $class = $topClass->createSubClass('Junk data generation (' . (new \DateTime())->format('Y-m-d H:i:s') . ')');

        $sampleFile = $ext->getDir().'data/items/sampleItem.xml';

        $generationData = [];
        for ($i = 0; $i < $count; $i++) {

            $report = ImportService::singleton()->importQTIFile($sampleFile, $class, false);
            /** @var \core_kernel_classes_Resource $item */
            $item = $report->getData();
            $item->setLabel(NameGenerator::generateTitle());

            $newLang = self::getRandomLanguage();
            Service::singleton()->getDataItemByRdfItem($item, $newLang, false);

            $newDir = \taoItems_models_classes_ItemsService::singleton()->getItemDirectory($item, $newLang);
            $langPath = $newDir->getFileSystem()->getAdapter()->getPathPrefix() . $newDir->getPrefix();
            if (!is_dir($langPath)) {
                mkdir($langPath, 0755);
            }
            copy($sampleFile, $langPath . '/qti.xml');
            touch($langPath . '/qti.xml', time()+1);

            $generationData[] = [
                'uri'   => $item->getUri(),
                'name'  => $item->getLabel(),
                'class' => $class,
                'langs' => [
                    DEFAULT_LANG,
                    $newLang
                ]
            ];
        }

        return $generationData;
    }
    
    public static function generateGlobalManager($count = 100) {
        $topClass = new \core_kernel_classes_Class(TaoOntology::CLASS_URI_TAO_USER);
        $role = new \core_kernel_classes_Resource(TaoOntology::PROPERTY_INSTANCE_ROLE_GLOBALMANAGER);
        $class = self::generateUsers($count, $topClass, $role, 'Backoffice user', 'user');
        
        return $class;
    }
    
    public static function generateTesttakers($count = 1000) {
        
        $ext = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoGroups');
        
        
        $topClass = new \core_kernel_classes_Class(TaoOntology::SUBJECT_CLASS_URI);
        $role = new \core_kernel_classes_Resource(TaoOntology::PROPERTY_INSTANCE_ROLE_DELIVERY);
        $class = self::generateUsers($count, $topClass, $role, 'Test-Taker ', 'tt');
        
        $groupClass = new \core_kernel_classes_Class(TaoOntology::GROUP_CLASS_URI);
        $group = $groupClass->createInstanceWithProperties(array(
            OntologyRdfs::RDFS_LABEL => $class->getLabel()
        ));
        
        foreach ($class->getInstances() as $user) {
            GroupsService::singleton()->addUser($user->getUri(), $group);
        }
        
        return $class;
    }
    
    protected static function generateUsers($count, $class, $role, $label, $prefix) {
        
        $userExists = \tao_models_classes_UserService::singleton()->loginExists($prefix.'0');
        if ($userExists) {
            throw new \common_exception_Error($label.' 0 already exists, Generator already run?');
        }
        
        $generationId = NameGenerator::generateRandomString(4);
        $subClass = $class->createSubClass('Generation '.$generationId);
        
        helpers_TimeOutHelper::setTimeOutLimit(helpers_TimeOutHelper::LONG);
        for ($i = 0; $i < $count; $i++) {
            $tt = $subClass->createInstanceWithProperties(array(
                OntologyRdfs::RDFS_LABEL => $label.' '.$i,
                GenerisRdf::PROPERTY_USER_UILG	=> 'http://www.tao.lu/Ontologies/TAO.rdf#Langen-US',
                GenerisRdf::PROPERTY_USER_DEFLG => 'http://www.tao.lu/Ontologies/TAO.rdf#Langen-US',
                GenerisRdf::PROPERTY_USER_LOGIN	=> $prefix.$i,
                GenerisRdf::PROPERTY_USER_PASSWORD => \core_kernel_users_Service::getPasswordHash()->encrypt('pass'.$i),
                GenerisRdf::PROPERTY_USER_ROLES => $role,
                GenerisRdf::PROPERTY_USER_FIRSTNAME => $label.' '.$i,
                GenerisRdf::PROPERTY_USER_LASTNAME => 'Family '.$generationId
            ));
        }
        
        helpers_TimeOutHelper::reset();
        return $subClass;
    }

    /**
     * Get languages list
     *
     * @return array
     */
    private static function getLanguagesList()
    {
        $availableLangs = \tao_helpers_I18n::getAvailableLangsByUsage(new \core_kernel_classes_Resource(TaoOntology::PROPERTY_STANCE_LANGUAGE_USAGE_DATA));
        return array_keys($availableLangs);
    }

    /**
     * Get random language from list of languages
     *
     * @return string A string represents IETF language code
     */
    private static function getRandomLanguage()
    {
        $langList = self::getLanguagesList();

        $randomLanguage = DEFAULT_LANG;
        while ($randomLanguage === DEFAULT_LANG) {
            $randomLanguage = $langList[array_rand(self::getLanguagesList())];
        }

        return $randomLanguage;
    }
}
