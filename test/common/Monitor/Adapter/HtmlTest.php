<?php
/**
 * Html test case
 */

namespace TaoTest\Generis\Test\Monitor\Adapter;

use oat\taoDevTools\helper\PhpunitTestCase;
use oat\taoDevTools\models\Monitor\OutputAdapter\Html;
use org\bovigo\vfs\vfsStream;
/**
 * Class HtmlTest
 * @package Tao\Generis\Test\Monitor\Adapter
 * @group monitor
 */
class HtmlTest extends PhpunitTestCase
{
    /**
     * @var \common_monitor_Adapter_Html
     */
    protected $instance;

    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    protected $root;

    public function setUp() {

        if(!defined('FILES_PATH')) {
            define('FILES_PATH', 'vfs://root/tmp/');
        }

        $this->root = vfsStream::setup('root',777, ['tmp' => []]);
        $this->instance = new Html();
    }


    public function fileNameProvider() {
        return
        [
            [
                'short-file-name.html', 'short-file-name.html',
            ],
            [
                'long-file-name-long-file-name-long-file-name-long-file-name-long-file-name-long-file-name-long-file-name-long-file-name-' . //120 char
                'long-file-name-long-file-name-long-file-name-long-file-name-long-file-name-long-file-name-long-file-name-long-file-name-' .
                'very-long.html',
                'long-file-name-long-file-name-long-file-name-long-file-name-long-file-name-long-file-name-long-file-name-long-file-name-' . //120 char
                'long-file-name-long-file-name-long-file-name-long-file-name-long-file-name-long-file-name-long-file-name-long-file-name-' .
                'very00254.html',
            ]
        ];
    }

    /**
     * @dataProvider fileNameProvider
     * @param $filename
     * @param $expected
     */
    public function testWriteFile($filename, $expected) {


        $fixtureData = '<html><body></body></html>';

        $filePath = 'vfs://root/tmp/';

        $this->instance->setFilePath($filePath);

        $this->instance->writeFile($filename, $fixtureData);

        $this->assertTrue($this->root->getChild('tmp')->hasChild($expected));

        $this->assertEquals($fixtureData, file_get_contents($filePath . $expected));

    }
}