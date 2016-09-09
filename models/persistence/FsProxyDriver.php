<?php
/**
 * @Author      Antoine Delamarre <antoine.delamarre@vesperiagroup.com>
 * @Date        10/02/15
 * @File        ProxyDriver.php
 * @Copyright   Copyright (c) Doctena - All rights reserved
 * @Licence     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */

namespace oat\taoDevTools\models\persistence;

use oat\oatbox\service\ConfigurableService;
use League\Flysystem\AdapterInterface;
use oat\oatbox\filesystem\utils\FlyWrapperTrait;
use oat\oatbox\filesystem\FileSystemService;

class FsProxyDriver extends ConfigurableService implements AdapterInterface
{
    use FlyWrapperTrait;

    private $count = 0;

    protected $adapter;

    /**
     * (non-PHPdoc)
     * @see \oat\oatbox\filesystem\FlyWrapperTrait::getAdapter()
     */
    public function getAdapter()
    {
        $this->count++;
        if (is_null($this->adapter)) {
            $fss = $this->getServiceManager()->get(FileSystemService::SERVICE_ID);
            $this->adapter = $fss->getFileSystem($this->getOption('inner'))->getAdapter();
        }
        return $this->adapter;
    }
   
    public function __destruct()
    {
        \common_Logger::i($this->count.' calls to filesystem '.$this->getOption('inner'));
    }
}