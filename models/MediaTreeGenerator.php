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

use common_ext_ExtensionsManager;
use core_kernel_classes_Class as RdfClass;
use oat\taoMediaManager\model\MediaService;

class MediaTreeGenerator extends AbstractTreeGenerator
{

    protected function provideOptions(): array
    {
        return
            array_merge(
                parent::provideOptions(),
                [
                    self::OPTION_ROOT_CLASS => [
                        'longPrefix' => 'root-class',
                        'prefix' => 'k',
                        'required' => false,
                        'description' => 'Root class',
                        'defaultValue' => MediaService::ROOT_CLASS_URI
                    ],
                ]
            );
    }


    protected function provideDescription(): string
    {
        return 'Tool to generate a tree of media assets';
    }

    protected function generateItem(RdfClass $class, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $sampleFile = $this->getAssetFilePath();

            $mediaResourceUri = $this->getAssetImportService()->createMediaInstance(
                $sampleFile,
                $class->getUri(),
                'en-US',
                basename($sampleFile)
            );

            if ($mediaResourceUri) {
                $media = $this->getResource($mediaResourceUri);
                $media->setLabel(sprintf('Media %s', $i));
                ++$this->itemsCount;
            }
        }
    }

    private function getAssetFilePath(): string
    {
        $ext = $this
            ->getServiceLocator()
            ->get(common_ext_ExtensionsManager::SERVICE_ID)
            ->getExtensionById('taoDevTools');

        $list = ['black.png', 'pink.png'];
        return sprintf('%sdata/assets/%s', $ext->getDir(), array_rand(array_flip($list)));
    }

    private function getAssetImportService(): MediaService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->serviceLocator->get(MediaService::class);
    }
}
