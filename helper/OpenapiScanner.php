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
use Symfony\Component\Finder\Finder;

/**
 * Wrapper for OpenApi\scan function with additional info
 */
class OpenapiScanner
{
    const MISSED_REF_ERROR_RE = '/\$ref "#\/components\/schemas\/(.*?)" not found for/';
    const PATH_NOT_FOUND_ERROR = 'Required @OA\Info() not found';
    const INFO_NOT_FOUND_ERROR = 'Required @OA\PathItem() not found';

    /**
     * @param string $directory
     * @param array $options
     * @return OpenapiScanner\ScanResult
     */
    public static function scan($directory, $options = [])
    {
        $analyser = array_key_exists('analyser', $options) ? $options['analyser'] : new \OpenApi\StaticAnalyser();
        $analysis = array_key_exists('analysis', $options) ? $options['analysis'] : new \OpenApi\Analysis();
        $processors = array_key_exists('processors', $options) ? $options['processors'] : \OpenApi\Analysis::processors();
        $exclude = array_key_exists('exclude', $options) ? $options['exclude'] : null;

        $otherErrors = [];
        self::addFiles($analysis, $analyser, \OpenApi\Util::finder($directory, $exclude), $otherErrors);

        // Post processing
        $analysis->process($processors);
        // Validation (Generate notices & warnings)
        $missedRefs = [];
        self::validate($analysis, $missedRefs, $otherErrors);

        return new OpenapiScanner\ScanResult($analysis, $missedRefs, $otherErrors);
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

        // To avoid duplication of already existed schemas in openapi
        $destAnalysis->openapi->components->schemas = [];
        // Post processing
        $destAnalysis->process(\OpenApi\Analysis::processors());
        // Validation (Generate notices & warnings)
        $missedRefs = $otherErrors = [];
        self::validate($destAnalysis, $missedRefs, $otherErrors);

        return new OpenapiScanner\ScanResult($destAnalysis, $missedRefs, $otherErrors);
    }

    /**
     * @param \OpenApi\Analysis $analysis
     * @param bool[] $missedRefs keys: ref, value: true (search optimization)
     * @param string[] $otherErrors
     * @return void missedRefs
     */
    protected static function validate(\OpenApi\Analysis $analysis, array &$missedRefs, array &$otherErrors) {
        /** @var callable $oldLogClosure */
        $oldLogClosure = \OpenApi\Logger::getInstance()->log;

        \OpenApi\Logger::getInstance()->log =
            function ($entry) use (&$missedRefs, &$otherErrors) {
                $matches = [];
                if (is_string($entry)) {
                    if (preg_match(self::MISSED_REF_ERROR_RE, $entry, $matches)) {
                        $missedRefs[$matches[1]] = true;
                        return;
                    }
                    if ($entry === self::INFO_NOT_FOUND_ERROR || $entry === self::PATH_NOT_FOUND_ERROR) {
                        return;
                    }
                }

                if ($entry instanceof \Exception) {
                    $entry = $entry->getMessage();
                }

                $otherErrors[] = $entry;
            };

        try {
            @$analysis->validate();
        }
        finally {
            \OpenApi\Logger::getInstance()->log = $oldLogClosure;
        }
    }

    protected static function addFiles(
        \OpenApi\Analysis $analysis,
        \OpenApi\StaticAnalyser $analyser,
        Finder $finder,
        array &$otherErrors
    ) {
        /** @var callable $oldLogClosure */
        $oldLogClosure = \OpenApi\Logger::getInstance()->log;

        \OpenApi\Logger::getInstance()->log =
            function ($entry) use (&$otherErrors) {
                if ($entry instanceof \Exception) {
                    $entry = $entry->getMessage();
                }
                $otherErrors[] = $entry;
            };

        try {
            // Crawl directory and parse all files
            foreach ($finder as $file) {
                $analysis->addAnalysis($analyser->fromFile($file->getPathname()));
            }
        }
        finally {
            \OpenApi\Logger::getInstance()->log = $oldLogClosure;
        }
    }
}