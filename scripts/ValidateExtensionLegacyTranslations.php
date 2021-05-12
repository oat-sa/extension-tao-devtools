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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoDevTools\scripts;

use common_exception_Error;
use oat\oatbox\extension\script\ScriptAction;
use oat\oatbox\reporting\Report;

/**
 * Class ValidateExtensionLegacyTranslations
 *
 * @package oat\taoDevTools\scripts
 *
 * @example php index.php "oat\taoDevTools\scripts\ValidateExtensionLegacyTranslations" --extension taoDevTools
 */
class ValidateExtensionLegacyTranslations extends ScriptAction
{
    /**
     * @var Report
     */
    private $report;

    /**
     * @return array
     */
    protected function provideOptions()
    {
        return [
            'extension' => [
                'prefix' => 'e',
                'longPrefix' => 'extension',
                'required' => true,
                'description' => 'Translatable extension.'
            ],
        ];
    }

    /**
     * @return string
     */
    protected function provideDescription()
    {
        return 'A script to ensure legacy strings are not within the extension.';
    }

    /**
     * @return Report
     * @throws common_exception_Error
     */
    protected function run()
    {
        $this->report = new Report(Report::TYPE_INFO, 'Search for legacy strings');

        $dir = $this->getOption('extension');
        $this->scanDirectory($dir);

        $this->report->add(new Report(Report::TYPE_SUCCESS, 'Search for legacy strings completed.'));
        return $this->report;
    }

    /**
     * @param string $dir
     * @throws common_exception_Error
     */
    private function scanDirectory(string $dir)
    {
        $filesOrDirectories = scandir($dir);

        foreach ($filesOrDirectories as $fd) {
            if (substr($fd, 0, 1) === '.') {
                continue;
            }
            if (is_dir($dir . '/' . $fd)) {
                $this->scanDirectory("$dir/$fd");
            } else {
                $this->scanFile("$dir/$fd");
            }
        }
    }

    /**
     * @param string $file
     * @throws common_exception_Error
     */
    private function scanFile(string $file)
    {
        if (strpos(__FILE__, $file)) {
            return;
        }

        $arrayLines = file($file);
        foreach ($arrayLines as $number => $line) {
            if (strpos($line, '__(')) {
                $str = strstr(strstr($line, '__('), ')', true);
                if (!strpos($str, $this->getOption('extension') . '.')) {
                    $msgError = sprintf("Inappropriate format. File: %s. String(%s): %s)", $file, ++$number, $str);
                    $this->report->add(
                        new Report(Report::TYPE_ERROR, $msgError)
                    );
                }
            }
        }
    }
}
