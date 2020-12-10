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
use core_kernel_classes_Class as RdfClass;
use oat\taoQtiItem\model\qti\ImportService;

class ItemTreeGenerator extends AbstractTreeGenerator
{
    /**
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_ext_ExtensionException
     */
    protected function generateItem(RdfClass $class, int $count): void
    {
        $sampleFile = $this->getQtiFilePath();

        for ($i = 0; $i < $count; $i++) {
            $report = $this->getImportService()->importQTIFile($sampleFile, $class, false);
            $item = $report->getData();
            $item->setLabel(sprintf('Item %s', $i));
            ++$this->itemsCount;
        }
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
