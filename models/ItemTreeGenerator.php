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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\taoDevTools\models;

use common_Exception;
use common_exception_Error;
use common_ext_ExtensionException;
use common_ext_ExtensionsManager;
use common_report_Report;
use core_kernel_classes_Class as RdfClass;
use Exception;
use oat\oatbox\extension\script\ScriptAction;
use oat\tao\model\TaoOntology;
use oat\taoDevTools\helper\NameGenerator;
use oat\taoQtiItem\model\qti\ImportService;
use RuntimeException;

class ItemTreeGenerator extends ScriptAction
{
    private const OPTION_ITEMS_COUNT = 'items_count';
    private const OPTION_CLASS_COUNT = 'class_count';
    private const OPTION_NESTING_LEVEL = 'nesting_level';
    private const OPTION_OWN_ROOT = 'own_root';
    private const OPTION_ROOT_CLASS = 'root_class';

    private $classesCount = 0;
    private $itemsCount = 0;

    protected function provideOptions(): array
    {
        return [
            self::OPTION_ITEMS_COUNT   => [
                'longPrefix'   => 'items-count',
                'prefix'       => 'i',
                'required'     => false,
                'description'  => 'Number of items in class. Can be int or range. Example: 5, 1-5',
                'defaultValue' => '2'
            ],
            self::OPTION_CLASS_COUNT   => [
                'longPrefix'   => 'class-count',
                'prefix'       => 'c',
                'required'     => false,
                'description'  => 'Number of classes in class. Can be int or range. Example: 5, 1-5\',',
                'defaultValue' => '2'
            ],
            self::OPTION_NESTING_LEVEL => [
                'longPrefix'   => 'nesting-level',
                'prefix'       => 'n',
                'required'     => false,
                'description'  => 'Nesting level. Can be int or range. Example: 5, 1-5\',',
                'defaultValue' => '3'
            ],
            self::OPTION_OWN_ROOT      => [
                'longPrefix'   => self::OPTION_OWN_ROOT,
                'prefix'       => 'r',
                'required'     => false,
                'description'  => 'All classes create under own root',
                'defaultValue' => true
            ],
            self::OPTION_ROOT_CLASS    => [
                'longPrefix'   => 'root-class',
                'prefix'       => 'k',
                'required'     => false,
                'description'  => 'Root class',
                'defaultValue' => TaoOntology::CLASS_URI_ITEM
            ],
        ];
    }

    protected function provideUsage(): array
    {
        return [
            'prefix' => 'h',
            'longPrefix' => 'help',
            'description' => 'Prints a help statement'
        ];
    }

    protected function provideDescription(): string
    {
        return 'Tool to generate items';
    }

    /**
     * @return common_report_Report|void
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_ext_ExtensionException
     */
    protected function run()
    {
        $rootClass = new RdfClass($this->getOption(self::OPTION_ROOT_CLASS));

        $ownRoot = filter_var($this->getOption(self::OPTION_OWN_ROOT), FILTER_VALIDATE_BOOLEAN);

        if ($ownRoot) {
            $rootClass = $this->createClass($rootClass);
        }

        $this->createClasses($rootClass, 1);

        return common_report_Report::createSuccess(
            sprintf(
                '%s classes, %s items created. Root class: %s',
                $this->classesCount,
                $this->itemsCount,
                $rootClass->getUri()
            )
        );
    }

    /**
     * @param RdfClass $parentClass
     * @param int      $level
     *
     * @throws Exception
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_ext_ExtensionException
     */
    private function createClasses(RdfClass $parentClass, int $level): void
    {
        $classCount = $this->getClassCount();

        for ($i = 0; $i < $classCount; ++$i) {
            $class = $this->createClass($parentClass);

            if ($this->getNestingLevel() > $level) {
                $this->createClasses($class, $level + 1);
            }

            $this->generateItem($class, $this->getItemCount());
        }
    }

    /**
     * @return int
     *
     * @throws Exception
     */
    private function getItemCount(): int
    {
        return $this->parseRangeValue(
            $this->getOption(self::OPTION_ITEMS_COUNT)
        );
    }

    /**
     * @return int
     *
     * @throws Exception
     */
    private function getClassCount(): int
    {
        return $this->parseRangeValue(
            $this->getOption(self::OPTION_CLASS_COUNT)
        );
    }

    /**
     * @return int
     *
     * @throws Exception
     */
    private function getNestingLevel(): int
    {
        return $this->parseRangeValue(
            $this->getOption(self::OPTION_NESTING_LEVEL)
        );
    }

    /**
     * @param string $value
     *
     * @return int
     * @throws Exception
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

        throw new RuntimeException('Value should be an integer or a range in format %d-%d');
    }

    /**
     * @param RdfClass $class
     * @param int      $count
     *
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_ext_ExtensionException
     */
    private function generateItem(RdfClass $class, int $count): void
    {
        $sampleFile = $this->getQtiFilePath();

        for ($i = 0; $i < $count; $i++) {
            $report = $this->getImportService()->importQTIFile($sampleFile, $class, false);
            $item = $report->getData();
            $item->setLabel(sprintf('Item_%s', $i));
            ++$this->itemsCount;
        }
    }

    private function createClass(RdfClass $parentClass): RdfClass
    {
        $generationId = NameGenerator::generateRandomString(4);

        ++$this->classesCount;

        return $parentClass->createSubClass('Generation ' . $generationId);
    }

    private function getQtiFilePath(): string
    {
        $ext = $this
            ->getServiceLocator()
            ->get(common_ext_ExtensionsManager::SERVICE_ID)
            ->getExtensionById('taoDevTools');

        return $ext->getDir() . 'data/items/sampleItem.xml';
    }

    private function getImportService(): ImportService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->serviceLocator->get(ImportService::SERVICE_ID);
    }
}
