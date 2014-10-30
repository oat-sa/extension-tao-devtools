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

use oat\taoQtiItem\model\qti\ImportService;
use helpers_TimeOutHelper;
use oat\taoTestTaker\models\TestTakerService;

class DataGenerator
{
    public static function generateItems($count = 100) {
        // load QTI constants
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoQtiItem');
        
        $ext = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDevTools');
        
        $generationId = NameGenerator::generateRandomString(4);
        
        $topClass = new \core_kernel_classes_Class(TAO_ITEM_CLASS);
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
    
    public static function generateTesttakers($count = 100) {
        
        $ext = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoGroups');
        $topClass = new \core_kernel_classes_Class(TAO_SUBJECT_CLASS);
        
        // ensure tts don't exist already
        $tt0Exists = \tao_models_classes_UserService::singleton()->loginExists('tt0');
        if ($tt0Exists) {
            throw new \common_exception_Error('Testtaker 0 already exists, Generator already run?');
        }
        
        $generationId = NameGenerator::generateRandomString(4);
        
        helpers_TimeOutHelper::setTimeOutLimit(helpers_TimeOutHelper::LONG);
        
        
        $class = new \core_kernel_classes_Class(TAO_GROUP_CLASS);
        $group = $class->createInstanceWithProperties(array(
            RDFS_LABEL => 'Generation '.$generationId
        ));
        
        $class = $topClass->createSubClass('Generation '.$generationId);
        for ($i = 0; $i < $count; $i++) {
            $tt = $class->createInstanceWithProperties(array(
                RDFS_LABEL => 'Test taker '.$i,
                PROPERTY_USER_UILG	=> 'http://www.tao.lu/Ontologies/TAO.rdf#Langen-US',
                PROPERTY_USER_DEFLG => 'http://www.tao.lu/Ontologies/TAO.rdf#Langen-US',
                PROPERTY_USER_LOGIN	=> 'tt'.$i,
                PROPERTY_USER_PASSWORD => \core_kernel_users_Service::getPasswordHash()->encrypt('pass'.$i),
                PROPERTY_USER_ROLES => 'http://www.tao.lu/Ontologies/TAO.rdf#DeliveryRole',
                PROPERTY_USER_FIRSTNAME => 'Testtaker '.$i,
                PROPERTY_USER_LASTNAME => 'Family '.$generationId
            ));
            $group->setPropertyValue(new \core_kernel_classes_Property(TAO_GROUP_MEMBERS_PROP), $tt);
        }
        
        helpers_TimeOutHelper::reset();
        return $class;
    }
}
