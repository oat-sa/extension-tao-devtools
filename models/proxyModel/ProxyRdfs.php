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

use oat\generis\model\data\RdfsInterface;

class ProxyRdfs implements RdfsInterface
{
    /**
     * @var core_kernel_persistence_smoothsql_SmoothModel
     */
    private $models;

    public function __construct(array $models)
    {
        $this->models = $models;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\generis\model\data\RdfsInterface::getClassImplementation()
     */
    public function getClassImplementation()
    {
        return new ProxyClass($this->models);
    }

    /**
     * (non-PHPdoc)
     * @see \oat\generis\model\data\RdfsInterface::getResourceImplementation()
     */
    public function getResourceImplementation()
    {
        return new ProxyResource($this->models);
    }

    /**
     * (non-PHPdoc)
     * @see \oat\generis\model\data\RdfsInterface::getPropertyImplementation()
     */
    public function getPropertyImplementation()
    {
        return new  ProxyProperty($this->models);
    }
}