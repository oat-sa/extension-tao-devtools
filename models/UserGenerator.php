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
 * Copyright (c) 2023 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoDevTools\models;


use common_exception_Error;
use common_report_Report;
use core_kernel_classes_Class;
use helpers_TimeOutHelper;
use oat\generis\model\GenerisRdf;
use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdfs;
use oat\generis\model\user\PasswordConstraintsException;
use oat\oatbox\extension\script\ScriptAction;
use oat\oatbox\reporting\Report;
use oat\tao\model\TaoOntology;
use oat\tao\model\user\TaoRoles;
use oat\taoDevTools\helper\DataGenerator;
use oat\taoDevTools\helper\NameGenerator;

/**
 * Generates user with parameters
 * php index.php oat\taoDevTools\models\UserGenerator -r http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole -p third -c 100
 * php index.php oat\taoDevTools\models\UserGenerator -r http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorAdministratorRole -p third -c 100
 * php index.php oat\taoDevTools\models\UserGenerator -r http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorAdministratorRole -p third -c 100
 *
 * Class UserGenerator
 * @package oat\taoDevTools\models
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
        $report = Report::createInfo('Generation started');
        $prefix = $this->hasOption('prefix') ? $this->getOption('prefix') : '';
        // TaoRoles or specified for the extension
        $roles = $this->getOption('roles');
        $count = $this->getOption('count');
        $topClass = $this->getOption('class');

        $class = $this->getClass($topClass);
        if (!$class->exists()) {
            return Report::createError(sprintf('Class "%s" does not exist', $class->getUri()));
        }

        $role = $this->getResource($roles);
        if (!$role->exists()) {
            return Report::createError(sprintf('Role "%s" does not exist', $role->getUri()));
        }

        try {
            DataGenerator::generateUsers($count, $class, $roles, $prefix . ' Label', $prefix);
            $report->add(Report::createSuccess(sprintf('Generated %s users', $count)));
        } catch (\Throwable $e) {
            $report->add(Report::createError($e->getMessage()));
        }

        return $report;
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
