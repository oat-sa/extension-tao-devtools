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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoDevTools\helper\OpenapiScanner;

class ScanResult
{
    /**
     * @var \OpenApi\Analysis
     */
    public $analysis;

    /**
     * @var bool[] with string keys (search optimization)
     */
    public $missedSchemaRefs;

    /**
     * @param \OpenApi\Analysis $analysis
     * @param string[] $missedSchemaRefs
     */
    public function __construct(\OpenApi\Analysis $analysis, $missedSchemaRefs = [])
    {
        $this->analysis = $analysis;
        $this->missedSchemaRefs = $missedSchemaRefs;
    }

    /**
     * @return bool
     */
    public function hasMissedRefs() {
        return \count($this->missedSchemaRefs) > 0;
    }

    /**
     * @return \OpenApi\Annotations\OpenApi
     */
    public function getOpenApi() {
        return $this->analysis->openapi;
    }

    /**
     * @return bool
     */
    public function hasParsedAnnotations() {
        return
            ($openApi = $this->getOpenApi()) && (
                $openApi->components !== \OpenApi\UNDEFINED ||
                $openApi->info !== \OpenApi\UNDEFINED ||
                $openApi->paths !== \OpenApi\UNDEFINED
            );
    }

    /**
     * @return bool
     */
    public function hasPathItems() {
        return ($openApi = $this->getOpenApi()) &&  $openApi->paths !== \OpenApi\UNDEFINED;
    }

    /**
     * @return \OpenApi\Annotations\Schema[]
     */
    public function getSchemas() {
        if (($openApi = $this->getOpenApi()) &&
            $openApi->components !== \OpenApi\UNDEFINED &&
            $openApi->components->schemas !== \OpenApi\UNDEFINED)
        {
            return $openApi->components->schemas;
        }
        return [];
    }

    /**
     * @return bool[] with string keys (search optimization)
     */
    public function getPossibleRefsExtensions() {
        $result = [];
        foreach ($this->missedSchemaRefs as $refName => $bool) {
            $matches = [];
            if (preg_match('/(\w*?)\.\w\w*?/', $refName, $matches)) {
                $result[$matches[1]] = true;
            }
        }

        return array_keys($result);
    }
}