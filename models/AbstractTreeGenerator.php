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
use common_report_Report;
use core_kernel_classes_Class as RdfClass;
use Exception;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\script\ScriptAction;
use oat\tao\model\TaoOntology;
use oat\taoDevTools\helper\NameGenerator;
use RuntimeException;

abstract class AbstractTreeGenerator extends ScriptAction
{
    use OntologyAwareTrait;

    protected const OPTION_ROOT_CLASS = 'root_class';
    private const OPTION_ITEMS_COUNT = 'items_count';
    private const OPTION_CLASS_COUNT = 'class_count';
    private const OPTION_NESTING_LEVEL = 'nesting_level';
    private const OPTION_OWN_ROOT = 'own_root';

    protected $itemsCount = 0;
    private $classesCount = 0;

    abstract protected function generateItem(RdfClass $class, int $count): void;

    protected function provideOptions(): array
    {
        return [
            self::OPTION_ITEMS_COUNT => [
                'longPrefix' => 'items-count',
                'prefix' => 'i',
                'required' => false,
                'description' => 'Number of items in class. Can be int or range. Example: 5, 1-5',
                'defaultValue' => '2'
            ],
            self::OPTION_CLASS_COUNT => [
                'longPrefix' => 'class-count',
                'prefix' => 'c',
                'required' => false,
                'description' => 'Number of classes in class. Can be int or range. Example: 5, 1-5',
                'defaultValue' => '2'
            ],
            self::OPTION_NESTING_LEVEL => [
                'longPrefix' => 'nesting-level',
                'prefix' => 'n',
                'required' => false,
                'description' => 'Nesting level. Can be int or range. Example: 5, 1-5',
                'defaultValue' => '3'
            ],
            self::OPTION_OWN_ROOT => [
                'longPrefix' => self::OPTION_OWN_ROOT,
                'prefix' => 'r',
                'required' => false,
                'description' => 'Create a tree under individual root',
                'defaultValue' => true
            ],
            self::OPTION_ROOT_CLASS => [
                'longPrefix' => 'root-class',
                'prefix' => 'k',
                'required' => false,
                'description' => 'Root class',
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
        return 'Tool to generate a tree of items';
    }

    /**
     * @return common_report_Report|void
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_ext_ExtensionException
     */
    protected function run()
    {
        $rootClass = $this->getClass($this->getOption(self::OPTION_ROOT_CLASS));

        $ownRoot = filter_var($this->getOption(self::OPTION_OWN_ROOT), FILTER_VALIDATE_BOOLEAN);

        if ($ownRoot) {
            $rootClass = $this->createClass($rootClass);
        }

        $this->createClasses($rootClass, 1);

        if ($ownRoot) {
            $rootClass->setLabel(
                $this->createClassName(1, $this->classesCount)
            );
        }

        return common_report_Report::createSuccess(
            sprintf(
                '%s classes, %s items created. Root class: "%s" (%s)',
                $this->classesCount,
                $this->itemsCount,
                $rootClass->getLabel(),
                $rootClass->getUri()
            )
        );
    }

    /**
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

            $from = $this->classesCount;

            if ($this->getNestingLevel() > $level) {
                $this->createClasses($class, $level + 1);
            }

            $class->setLabel(
                $this->createClassName($from, $this->classesCount)
            );

            $this->generateItem($class, $this->getItemCount());
        }
    }

    private function createClass(RdfClass $parentClass, ?string $name = null): RdfClass
    {
        ++$this->classesCount;

        return $parentClass->createSubClass($name);
    }

    private function createClassName(int $from, int $to): string
    {
        $generationId = NameGenerator::generateRandomString(4);

        return $from === $to
            ? sprintf('Class %s %s ', $from, $generationId)
            : sprintf('Class %s-%s %s ', $from, $to, $generationId);
    }

    /**
     * @throws Exception
     */
    private function getItemCount(): int
    {
        return $this->parseRangeValue(
            $this->getOption(self::OPTION_ITEMS_COUNT)
        );
    }

    /**
     * @throws Exception
     */
    private function getClassCount(): int
    {
        return $this->parseRangeValue(
            $this->getOption(self::OPTION_CLASS_COUNT)
        );
    }

    /**
     * @throws Exception
     */
    private function getNestingLevel(): int
    {
        return $this->parseRangeValue(
            $this->getOption(self::OPTION_NESTING_LEVEL)
        );
    }

    /**
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

}