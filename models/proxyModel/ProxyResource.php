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
 * Copyright (c) 2002-2008 (original work) 2014 Open Assessment Technologies SA
 *
 */

namespace oat\taoDevTools\models\proxyModel;


use core_kernel_classes_Class;
use core_kernel_classes_ContainerCollection;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use properties;

class ProxyResource implements \core_kernel_persistence_ResourceInterface
{
    private $models;

    public function __construct($models)
    {
        $this->models = $models;
    }

    public function call($method, array $arguments)
    {
        try {
            $debug = debug_backtrace();
            $previousCall = $debug[1]['class'];

            switch ($previousCall) {
                case 'oat\taoDevTools\models\proxyModel\ProxyProperty':
                    $implementationFunction = 'getPropertyImplementation';
                    break;
                case 'oat\taoDevTools\models\proxyModel\ProxyClass':
                    $implementationFunction = 'getClassImplementation';
                    break;
                default:
                    $implementationFunction = 'getResourceImplementation';
                    break;
            }

            $previousResult = '';
            $invalid = false;
            $i=0;
            foreach ($this->models as $key => $model) {
                $implementation = $model->getRdfsInterface()->$implementationFunction();
                $result = call_user_func_array(array($implementation, $method), $arguments);

                if ($i > 0 && $result != $previousResult) {
                    $invalid = true;
                    break;
                }

                $previousResult = $result;
                $i++;
            }

            if ($invalid) {
                \common_Logger::e('Data integrity violation for method ' . $method);
                \common_Logger::i(print_r($previousResult, true));
                \common_Logger::i(print_r($result, true));
                \common_Logger::i(' --------------- ');
                throw new \Exception('Different results detected.');
            }

            return $result;
        } catch (\Exception $e) {
            \common_Logger::e($e->getMessage());
        }
    }

    public function getTypes(core_kernel_classes_Resource $resource)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function getPropertyValues(core_kernel_classes_Resource $resource, core_kernel_classes_Property $property, $options = array())
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function getPropertyValuesByLg(core_kernel_classes_Resource $resource, core_kernel_classes_Property $property, $lg)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function setPropertyValue(core_kernel_classes_Resource $resource, core_kernel_classes_Property $property, $object, $lg = null)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function setPropertiesValues(core_kernel_classes_Resource $resource, $properties)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function setPropertyValueByLg(core_kernel_classes_Resource $resource, core_kernel_classes_Property $property, $value, $lg)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function removePropertyValues(core_kernel_classes_Resource $resource, core_kernel_classes_Property $property, $options = array())
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function removePropertyValueByLg(core_kernel_classes_Resource $resource, core_kernel_classes_Property $property, $lg, $options = array())
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function getRdfTriples(core_kernel_classes_Resource $resource)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function getUsedLanguages(core_kernel_classes_Resource $resource, core_kernel_classes_Property $property)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function duplicate(core_kernel_classes_Resource $resource, $excludedProperties = array())
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function delete(core_kernel_classes_Resource $resource, $deleteReference = false)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function getPropertiesValues(core_kernel_classes_Resource $resource, $properties)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function setType(core_kernel_classes_Resource $resource, core_kernel_classes_Class $class)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function removeType(core_kernel_classes_Resource $resource, core_kernel_classes_Class $class)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }


}