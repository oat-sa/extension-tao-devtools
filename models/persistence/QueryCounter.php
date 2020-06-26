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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoDevTools\models\persistence;

use Psr\Log\LoggerAwareInterface;
use oat\oatbox\log\LoggerAwareTrait;

class QueryCounter implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private $count = 0;

    private $id;

    private $functions = [];

    public function __construct(string $persistenceId) {
        $this->id = $persistenceId;
    }

    function count(string $functionName, string $sqlStatement): void
    {
        //$this->logDebug('    '.$functionName.' '.substr(str_replace('\n', ' ', $sqlStatement), 0, 100));
        $this->count++;
        $this->functions[$functionName] = isset($this->functions[$functionName])
            ? $this->functions[$functionName] + 1
            : 1
        ;
    }

    public function __destruct()
    {
        $this->logInfo($this->count . ' queries to ' . $this->id, array_keys($this->functions));
    }
}
