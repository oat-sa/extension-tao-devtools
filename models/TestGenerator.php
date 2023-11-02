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
 * Copyright (c) 2023 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\taoDevTools\models;

use oat\oatbox\extension\script\ScriptAction;
use oat\oatbox\reporting\Report;
use oat\oatbox\reporting\ReportInterface;
use oat\taoDevTools\helper\DataGenerator;

/**
 * php index.php oat\taoDevTools\models\TestGenerator -c 100
 * php index.php oat\taoDevTools\models\TestGenerator -c 100 -f ~/project/taoDevTools/data/tests/test_100.zip
 */
class TestGenerator extends ScriptAction
{
    private const OPTION_TEST_COUNT = 'test_count';
    private const OPTION_SAMPLE_FILE = 'sample_file';

    protected function provideOptions(): array
    {
        return [
            self::OPTION_TEST_COUNT   => [
                'longPrefix'   => 'test-count',
                'prefix'       => 'c',
                'required'     => false,
                'description'  => 'Number of test generated. Can be int or range. Example: 5, 1-5',
                'defaultValue' => '2'
            ],
            self::OPTION_SAMPLE_FILE   => [
                'longPrefix'   => 'sample-file',
                'prefix'       => 'f',
                'required'     => false,
                'description'  => 'Path to custom sample file with test template in QTI 2.2 package.',
                'defaultValue' => null
            ],
        ];
    }

    protected function provideUsage(): array
    {
        return [
            'prefix'      => 'h',
            'longPrefix'  => 'help',
            'description' => 'Prints a help statement'
        ];
    }

    protected function provideDescription(): string
    {
        return 'Tool to generate a tests';
    }

    /**
     * @return ReportInterface
     * @throws \Exception
     */
    protected function run()
    {
        $filePath = $this->getSampleFile();
        $itemsCount = $this->getItemCount();

        DataGenerator::generateTests($itemsCount, $filePath);

        return Report::createSuccess(
            sprintf(
                '%s test(s) created using %s template.',
                $itemsCount,
                $filePath ?? 'default'
            )
        );
    }

    /**
     * @return int
     *
     * @throws \Exception
     */
    private function getItemCount(): int
    {
        return $this->parseRangeValue(
            $this->getOption(self::OPTION_TEST_COUNT)
        );
    }

    /**
     * @return int
     *
     * @throws \Exception
     */
    private function getSampleFile(): ?string
    {
        $filePath = $this->getOption(self::OPTION_SAMPLE_FILE);

        if (null !== $filePath && !file_exists($filePath)) {
            throw new \RuntimeException(sprintf('Sample file %s is not accessible.', $filePath));
        }

        return $filePath;
    }

    /**
     * @param string $value
     *
     * @return int
     * @throws \Exception
     */
    private function parseRangeValue(string $value): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }

        $result = explode('-', $value);

        if (count($result) === 2) {
            return random_int((int)$result[0], (int)$result[1]);
        }

        throw new \RuntimeException('Value should be an integer or a range in format %d-%d');
    }
}
