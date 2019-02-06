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

namespace oat\taoDevTools\helper;

use OpenApi\Annotations\OpenApi;

/**
 * Wrapper for OpenApi\scan function with additional info
 */
class OpenapiScanner
{
    /**
     * @param string $directory
     * @param array $options
     * @param bool $validate
     * @return OpenapiScanner\ScanResult
     */
    public static function scan($directory, $options = [])
    {
        $analyser = array_key_exists('analyser', $options) ? $options['analyser'] : new \OpenApi\StaticAnalyser();
        $analysis = array_key_exists('analysis', $options) ? $options['analysis'] : new \OpenApi\Analysis();
        $processors = array_key_exists('processors', $options) ? $options['processors'] : \OpenApi\Analysis::processors();
        $exclude = array_key_exists('exclude', $options) ? $options['exclude'] : null;

        // Crawl directory and parse all files
        $finder = \OpenApi\Util::finder($directory, $exclude);
        foreach ($finder as $file) {
            $analysis->addAnalysis($analyser->fromFile($file->getPathname()));
        }
        // Post processing
        $analysis->process($processors);
        // Validation (Generate notices & warnings)
        $missedRefs = self::validate($analysis);

        return new OpenapiScanner\ScanResult($analysis, $missedRefs);
    }

    /**
     * @param OpenApi $openApi
     * @return bool
     */
    public static function hasParsedAnnotations(OpenApi $openApi) {
        return
            $openApi->components !== \OpenApi\UNDEFINED ||
            $openApi->info !== \OpenApi\UNDEFINED ||
            $openApi->paths !== \OpenApi\UNDEFINED;
    }

    /**
     * @param \OpenApi\Analysis $destAnalysis
     * @param \OpenApi\Annotations\Schema[] $schemas
     * @return OpenapiScanner\ScanResult
     */
    public static function addSchemasToAnalysis(\OpenApi\Analysis $destAnalysis, $schemas)
    {
        foreach ($schemas as $schema) {
            $destAnalysis->addAnnotation($schema, $schema->_context);
        }

        // Post processing
        $destAnalysis->process(\OpenApi\Analysis::processors());
        // Validation (Generate notices & warnings)
        $missedRefs = self::validate($destAnalysis);

        return new OpenapiScanner\ScanResult($destAnalysis, $missedRefs);
    }

    /**
     * @param \OpenApi\Analysis $analysis
     * @return string[] missedRefs
     */
    protected static function validate(\OpenApi\Analysis $analysis) {
        /** @var callable $oldLogClosure */
        $oldLogClosure = \OpenApi\Logger::getInstance()->log;
        $missedRefs = [];

        try {

            \OpenApi\Logger::getInstance()->log =
                function ($entry, $type) use (&$missedRefs, $oldLogClosure) {
                    $matches = [];
                    if (is_string($entry) &&
                        preg_match('/\$ref "#\/components\/schemas\/(.*?)" not found for/', $entry, $matches)
                    ) {
                        $missedRefs[$matches[1]] = true;
                    }
                    else {
                        $oldLogClosure($entry, $type);
                    }
                };

            @$analysis->validate();
        }
        finally {
            \OpenApi\Logger::getInstance()->log = $oldLogClosure;
        }

        return $missedRefs;
    }
}