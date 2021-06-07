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
 * Copyright (c) 2019  (original work) Open Assessment Technologies SA;
 *
 * @author Oleksandr Zagovorychev <zagovorichev@gmail.com>
 */

namespace oat\taoDevTools\scripts\tools;


use common_exception_Error;
use common_report_Report;
use core_kernel_classes_Class;
use helpers_TimeOutHelper;
use oat\generis\model\GenerisRdf;
use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdfs;
use oat\generis\model\user\PasswordConstraintsException;
use oat\oatbox\extension\script\ScriptAction;
use oat\tao\model\TaoOntology;
use oat\tao\model\user\TaoRoles;
use oat\taoDevTools\helper\NameGenerator;

/**
 * Generates user with parameters
 * php index.php oat\taoDevTools\scripts\tools\UserGenerator -r http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole -p third -c 100
 * php index.php oat\taoDevTools\scripts\tools\UserGenerator -r http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorAdministratorRole -p third -c 100
 * php index.php oat\taoDevTools\scripts\tools\UserGenerator -r http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorAdministratorRole -p third -c 100
 *
 * Class UserGenerator
 * @package oat\taoDevTools\scripts\tools
 */
class UserGenerator extends ScriptAction
{
    use OntologyAwareTrait;

    protected function provideOptions()
    {
        return [
            'prefix' => [
                'prefix' => 'p',
                'longPrefix' => 'prefix',
                'required' => false,
                'description' => 'Prefix for user name and label',
                'defaultValue' => '',
            ],
            'roles' => [
                'prefix' => 'r',
                'longPrefix' => 'roles',
                'required' => false,
                'description' => 'Roles assigned to the user',
                'defaultValue' => TaoRoles::BASE_USER,
            ],
            'count' => [
                'prefix' => 'c',
                'longPrefix' => 'root',
                'description' => 'Count of users',
                'defaultValue' => 1,
                'cast' => 'integer'
            ],
            'class' => [
                'prefix' => 'l',
                'longPrefix' => 'class',
                'description' => 'Top class for the users generator',
                'defaultValue' => TaoOntology::CLASS_URI_TAO_USER,
            ]
        ];
    }

    protected function provideDescription()
    {
        return 'Generate new users for the system';
    }

    protected function run()
    {
        $report = common_report_Report::createInfo('Generation started');
        $prefix = $this->hasOption('prefix') ? $this->getOption('prefix') : '';
        // TaoRoles or specified for the extension
        $roles = $this->getOption('roles');
        $count = $this->getOption('count');
        $topClass = $this->getOption('class');

        $class = $this->getClass($topClass);
        if (!$class->exists()) {
            return common_report_Report::createFailure(sprintf('Class "%s" does not exist', $class->getUri()));
        }

        $role = $this->getResource($roles);
        if (!$role->exists()) {
            return common_report_Report::createFailure(sprintf('Role "%s" does not exist', $role->getUri()));
        }

        try {
            $this->generateUsers($count, $class, $roles, $prefix . ' Label', $prefix);
            $report->add(common_report_Report::createSuccess(sprintf('Generated %s users', $count)));
        } catch (common_exception_Error $e) {
            try {
                $report->add(common_report_Report::createFailure($e->getMessage()));
            } catch (common_exception_Error $e) {
                die($e->getMessage());
            }
        }

        return $report;
    }

    /**
     * @param $count
     * @param $class
     * @param $role
     * @param $label
     * @param $prefix
     * @return mixed
     * @throws PasswordConstraintsException
     * @throws common_exception_Error
     */
    private function generateUsers($count, core_kernel_classes_Class $class, $role, $label, $prefix)
    {
        $userExists = \tao_models_classes_UserService::singleton()->loginExists($prefix.'0');
        if ($userExists) {
            throw new common_exception_Error($label.' 0 already exists, Generator already run?');
        }

        $generationId = NameGenerator::generateRandomString(4);
        // to be improved if needed
        // $subClass = $class->createSubClass('Generation '.$generationId);
        // $class = $subClass;

        helpers_TimeOutHelper::setTimeOutLimit(helpers_TimeOutHelper::LONG);
        for ($i = 0; $i < $count; $i++) {
            $class->createInstanceWithProperties(array(
                OntologyRdfs::RDFS_LABEL => $label.' '.$i,
                GenerisRdf::PROPERTY_USER_UILG	=> 'http://www.tao.lu/Ontologies/TAO.rdf#Langen-US',
                GenerisRdf::PROPERTY_USER_DEFLG => 'http://www.tao.lu/Ontologies/TAO.rdf#Langen-US',
                GenerisRdf::PROPERTY_USER_LOGIN	=> $prefix.$i,
                GenerisRdf::PROPERTY_USER_PASSWORD => \core_kernel_users_Service::getPasswordHash()->encrypt('Password_'.$i),
                GenerisRdf::PROPERTY_USER_ROLES => $role,
                GenerisRdf::PROPERTY_USER_FIRSTNAME => $label.' '.$i,
                GenerisRdf::PROPERTY_USER_LASTNAME => 'Family '.$generationId
            ));
        }

        helpers_TimeOutHelper::reset();
        return $class;
    }

    protected function provideUsage()
    {
        return [
            'prefix' => 'h',
            'longPrefix' => 'help',
            'description' => 'Prints a help statement'
        ];
    }
}
