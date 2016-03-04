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

use comment;
use core_kernel_classes_Class;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use properties;

class ProxyClass extends ProxyResource implements \core_kernel_persistence_ClassInterface
{
    public function getSubClasses(core_kernel_classes_Class $resource, $recursive = false)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function setSubClassOf(core_kernel_classes_Class $resource, core_kernel_classes_Class $iClass)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function isSubClassOf(core_kernel_classes_Class $resource, core_kernel_classes_Class $parentClass)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function getParentClasses(core_kernel_classes_Class $resource, $recursive = false)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function getProperties(core_kernel_classes_Class $resource, $recursive = false)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function getInstances(core_kernel_classes_Class $resource, $recursive = false, $params = array())
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function createInstance(core_kernel_classes_Class $resource, $label = '', $comment = '', $uri = '')
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function createSubClass(core_kernel_classes_Class $resource, $label = '', $comment = '', $uri = '')
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function createProperty(core_kernel_classes_Class $resource, $label = '', $comment = '', $isLgDependent = false)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function searchInstances(core_kernel_classes_Class $resource, $propertyFilters = array(), $options = array())
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function countInstances(core_kernel_classes_Class $resource, $propertyFilters = array(), $options = array())
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function getInstancesPropertyValues(core_kernel_classes_Class $resource, core_kernel_classes_Property $property, $propertyFilters = array(), $options = array())
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function createInstanceWithProperties(core_kernel_classes_Class $type, $properties)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function deleteInstances(core_kernel_classes_Class $resource, $resources, $deleteReference = false)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

}