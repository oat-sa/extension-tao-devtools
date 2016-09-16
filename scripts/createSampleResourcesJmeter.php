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
$totalProctorNum = (isset($params[1]) && is_numeric($params[1]) && $params[1] !== 0)?$totalTtNum/$params[1]: $totalTtNum/ 20;
$totalProctorNum = ($totalProctorNum < 1)?1:$totalProctorNum;

$testTakerCrudService = oat\taoTestTaker\models\CrudService::singleton();
$userService = \tao_models_classes_UserService::singleton();
$testCenterService = \oat\taoProctoring\model\TestCenterService::singleton();
$proctorManagementService = \oat\taoProctoring\model\ProctorManagementService::singleton();
$testTakerService = \oat\taoTestTaker\models\TestTakerService::singleton();
$userClass = new \core_kernel_classes_Class(CLASS_TAO_USER);


//create sample group
$testCenter = $testCenterService->createInstance(
    new \core_kernel_classes_Class($testCenterService::CLASS_URI),
    'jmeter_test_center'
);

$tts = array();
$subClass = $testTakerService->createSubClass($testTakerService->getRootClass(), 'jmeter_test_taker_'.$totalTtNum);

$i = 0;
$ttNum = 1;
while($i < $totalTtNum){
    if($userService->loginAvailable('jmeter_TT_' . $ttNum)){
        $tt = $testTakerCrudService->createFromArray(array(
            PROPERTY_USER_LOGIN => 'jmeter_TT_' . $ttNum,
            PROPERTY_USER_PASSWORD => 'jmeter_TT_' . $ttNum,
            RDFS_LABEL => 'jmeter_tt' . $ttNum,
            PROPERTY_USER_FIRSTNAME => 'jmeter_tt_' . $ttNum,
            PROPERTY_USER_LASTNAME => 'jmeter_tt_' . $ttNum,
            RDF_TYPE => $subClass
        ));
        $tts[] = $tt->getUri();
        $testCenterService->addTestTaker($tt->getUri(), $testCenter);
        $i++;
    }
    $ttNum++;
}

$i = 0;
$proctorNum = 1;
while($i < $totalProctorNum){
    if($userService->loginAvailable('Jmeter_proctor_' . $proctorNum)){
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
    }
    $proctorNum++;
}


//create delivery
$tests = [];
$testClazz = new core_kernel_classes_Class(TAO_TEST_CLASS);
foreach($testClazz->getInstances(true) as $instance){
    $tests[$instance->getUri()] = $instance->getLabel();
}

$testUris = array_keys($tests);
if(!empty($testUris)){
    $test = new core_kernel_classes_Resource($testUris[0]);
    $label = __("Delivery of %s", $test->getLabel());
    $deliveryClass = new \core_kernel_classes_Class('http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDelivery');
    $report = \oat\taoDeliveryRdf\model\SimpleDeliveryFactory::create($deliveryClass, $test, $label);
    /** @var \core_kernel_classes_Resource $delivery */
    $delivery = $report->getData();
    //add delivery to eligible list
    \oat\taoProctoring\model\EligibilityService::singleton()->createEligibility($testCenter, $delivery);
    \oat\taoProctoring\model\EligibilityService::singleton()->setEligibleTestTakers($testCenter, $delivery, $tts);

    //assign tt to delivery
    \oat\taoProctoring\helpers\DeliveryHelper::assignTestTakers($tts, $delivery->getUri(), $testCenter->getUri());
} else {
    echo 'No test found';
}






