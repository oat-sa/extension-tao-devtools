<?php
/*
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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA ;
 *
 */

/**
 * Usage : php createSampleResourcesJmeter.php [NbOfTestTaker] [NbOfTestTakerByProctor]
 */

use oat\tao\model\TaoOntology;
use oat\generis\model\GenerisRdf;
use oat\generis\model\OntologyRdf;
use oat\generis\model\OntologyRdfs;

require_once dirname(__FILE__).'/../../taoDeliveryRdf/includes/constants.php';
require_once dirname(__FILE__).'/../../taoTests/includes/raw_start.php';
require_once dirname(__FILE__).'/../../tao/includes/raw_start.php';
require_once dirname(__FILE__).'/../../taoResultServer/includes/raw_start.php';


if(!common_ext_ExtensionsManager::singleton()->isEnabled('taoProctoring')){
    echo 'extension taoProctoring needs to be installed and enabled in order to run this script !';
    die();
}

foreach($todefine as $contant => $value){
    define($contant, $value);
}

$params = $argv;
array_shift($params);
$totalTtNum = (isset($params[0]))?$params[0]:500;
$ttByProctor = (isset($params[1]) && is_numeric($params[1]) && $params[1] !== 0)?$params[1]:20;
$totalProctorNum = $totalTtNum/$ttByProctor;
$totalProctorNum = ($totalProctorNum < 1)?1:$totalProctorNum;

$testTakerCrudService = oat\taoTestTaker\models\CrudService::singleton();
$userService = \tao_models_classes_UserService::singleton();
$testCenterService = \oat\taoProctoring\model\TestCenterService::singleton();
$proctorManagementService = \oat\taoProctoring\model\ProctorManagementService::singleton();
$testTakerService = \oat\taoTestTaker\models\TestTakerService::singleton();
$userClass = new \core_kernel_classes_Class(TaoOntology::CLASS_URI_TAO_USER);


//create delivery
$tests = [];
$testClazz = new core_kernel_classes_Class(TaoOntology::TEST_CLASS_URI);
foreach($testClazz->getInstances(true) as $instance){
    $tests[$instance->getUri()] = $instance->getLabel();
}

$testUris = array_keys($tests);
if(!empty($testUris)){
    $i = 0;
    $delivery = null;
    $deliveryClass = new \core_kernel_classes_Class('http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDelivery');
    while(is_null($delivery) && $i< count($testUris)){
        $test = new core_kernel_classes_Resource($testUris[$i]);
        $label = __("Delivery of %s", $test->getLabel());
        $report = \oat\taoDeliveryRdf\model\SimpleDeliveryFactory::create($deliveryClass, $test, $label);
        /** @var \core_kernel_classes_Resource $delivery */
        $delivery = $report->getData();
        $i++;
    }
    if(is_null($delivery)){
        echo 'No test compilable';
        die();
    }
} else {
    echo 'No test found';
    die();
}

$i = 0;
$proctorNum = 1;
$ttNum = 1;

$subClass = $testTakerService->createSubClass($testTakerService->getRootClass(), 'jmeter_test_taker_'.$totalTtNum);
while($i < $totalProctorNum){
    if($userService->loginAvailable('Jmeter_proctor_' . $proctorNum)){
        $tts = array();
        //create sample group
        $testCenter = $testCenterService->createInstance(
            new \core_kernel_classes_Class($testCenterService::CLASS_URI),
            'jmeter_test_center_'.$proctorNum
        );

        $proctor = $userService->addUser(
            'Jmeter_proctor_' . $proctorNum,
            'Jmeter_proctor_' . $proctorNum,
            new \core_kernel_classes_Resource("http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole"),
            $userClass
        );
        $proctor->setPropertyValue(
            new core_kernel_classes_Property($proctorManagementService::PROPERTY_ASSIGNED_PROCTOR_URI),
            $testCenter
        );
        $proctor->setPropertyValue(
            new core_kernel_classes_Property($proctorManagementService::PROPERTY_ADMINISTRATOR_URI),
            $testCenter
        );
        $i++;

        $j = 0;
        while($j < $ttByProctor){
            if($userService->loginAvailable('jmeter_TT_' . $ttNum)){
                $tt = $testTakerCrudService->createFromArray(array(
                    GenerisRdf::PROPERTY_USER_LOGIN => 'jmeter_TT_' . $ttNum,
                    GenerisRdf::PROPERTY_USER_PASSWORD => 'jmeter_TT_' . $ttNum,
                    OntologyRdfs::RDFS_LABEL => 'jmeter_tt' . $ttNum,
                    GenerisRdf::PROPERTY_USER_FIRSTNAME => 'jmeter_tt_' . $ttNum,
                    GenerisRdf::PROPERTY_USER_LASTNAME => 'jmeter_tt_' . $ttNum,
                    OntologyRdf::RDF_TYPE => $subClass
                ));
                $tts[] = $tt->getUri();
                $j++;
            }
            $ttNum++;
        }
        $testCenterService->addTestTaker($tt->getUri(), $testCenter);
        //add delivery to eligible list
        /** @var \oat\taoProctoring\model\EligibilityService $eligibilityService */
        $eligibilityService = \oat\oatbox\service\ServiceManager::getServiceManager()->get(\oat\taoProctoring\model\EligibilityService::SERVICE_ID);
        $eligibilityService->createEligibility($testCenter, $delivery);
        $eligibilityService->setEligibleTestTakers($testCenter, $delivery, $tts);

        //assign tt to delivery
        if($eligibilityService->isManageable()){
            \oat\taoProctoring\helpers\DeliveryHelper::assignTestTakers($tts, $delivery->getUri(), $testCenter->getUri());
        }
    }
    $proctorNum++;
}