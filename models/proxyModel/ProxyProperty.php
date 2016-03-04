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
use core_kernel_classes_Resource;

class ProxyProperty extends ProxyResource implements \core_kernel_persistence_PropertyInterface
{
    public function isLgDependent(core_kernel_classes_Resource $resource)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function isMultiple(core_kernel_classes_Resource $resource)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function getRange(core_kernel_classes_Resource $resource)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function setRange(core_kernel_classes_Resource $resource, core_kernel_classes_Class $class)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function setMultiple(core_kernel_classes_Resource $resource, $isMultiple)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function setLgDependent(core_kernel_classes_Resource $resource, $isLgDependent)
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

}