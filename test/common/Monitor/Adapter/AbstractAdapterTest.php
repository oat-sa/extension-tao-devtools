<?php
/**
 * AbstractAdapter test case
 */

namespace TaoTest\Generis\Test\Monitor\Adapter;
use oat\taoDevTools\helper\PhpunitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * Class MethodTest
 * @package Tao\Generis\Test\Monitor\Chunk
 * @group monitor
 * @group monitor/adapter
 */
class  AbstractAdapterTest extends PhpunitTestCase
{
    /**
     * @var \oat\taoDevTools\models\Monitor\OutputAdapter\AbstractAdapter
     */
    protected $instance;

    protected $root;

    public function setUp() {

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\OutputAdapter\AbstractAdapter', true);
        $this->root = vfsStream::setup('tmp',777);
    }

    /**
     * @param array $config
     */
    public function test__construct(array $config = null) {

        $fixtureConfig =
        [
            'option1' => 'value1',
            'option2' => 'value2'
        ];


        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\OutputAdapter\AbstractAdapter', true,
        [
            'setOption1' =>
            [
                'with' => ['value1']
            ],
            'setOption2' =>
            [
                'with' => ['value2']
            ],
            'init' => []
        ]);

        $this->instance->__construct($fixtureConfig);

    }

    /**
     * Get the duplicated Call grouped by method in an array
     * @param $calls
     *
     * @return array
     */
    public function testGetMergedDuplicatedCalls() {

        $fixtureCalls =
        [
            $call1 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getHash' => ['will' => 'hash1']]),
            $call11 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getHash' => ['will' => 'hash1']]),
            $call2 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getHash' => ['will' => 'hash2']]),
            $call3 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getHash' => ['will' => 'hash3']]),
            $call33 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getHash' => ['will' => 'hash3']]),
        ];

        $expected =
        [
            'hash1' => [$call1, $call11],
            'hash2' => [$call2],
            'hash3' => [$call3, $call33]
        ];

        $this->assertEquals($expected, $this->instance->getMergedDuplicatedCalls($fixtureCalls));
    }

    /**
     * traces provider
     * @return array
     */
    public function tracesProvider() {

        return
        [
            [
                [//traces
                    ['trace' => ['1', '2', '3', '4', '5']],
                    ['trace' => ['1', '2', '33', '4', '5']],
                    ['trace' => ['1', '2', '3', '33', '4', '5']],
                    ['trace' => ['1', '2', '4', '5']],
                ],
                4, //minSize
                ['1','2'], // Common start parts
                ['4', '5'] // Common Ending parts
            ],
            [
                [
                    ['trace' => ['1', '2', '3', '4', '5']],
                    ['trace' => ['1', '2', '3']],
                    ['trace' => ['1', '2', '3', '4']],
                    ['trace' => ['1', '2', '3']],
                ],
                3,
                ['1', '2', '3'],
                []
            ],
            [
                [
                    ['trace' => ['1']],
                    ['trace' => ['1', '2', '3']],
                    ['trace' => ['1', '2', '3', '4']],
                    ['trace' => ['1', '2', '3']],
                ],
                1,
                ['1'],
                []
            ],
            [
                [
                    ['trace' => []],
                    ['trace' => ['1', '2', '3']],
                    ['trace' => ['1', '2', '3', '4']],
                    ['trace' => ['1', '2', '3']],
                ],
                0,
                [],
                []
            ],
            [
                [
                    ['trace' => ['1', '2', '3']],
                ],
                3,
                ['1', '2', '3'],
                ['1', '2', '3']
            ],
            [
                [],
                0,
                [],
                []
            ]
        ];
    }

    /**
     * test getTraceMinSize
     * @dataProvider tracesProvider
     */
    public function testGetTraceMinSize($mergedTraces, $expected) {

        $this->assertEquals($expected, $this->instance->getTraceMinSize($mergedTraces));

    }

    /**
     * Provider array_intersect_assoc_strict
     * @return array
     */
    public function arrayIntersectProvider() {
        return
        [
            [
                ['1', '2', '3','4', '5'], ['1', '2', '3', '4'], ['1', '2', '3'],['1', '2', '5'],
                ['1', '2'] //array strict intersection
            ],
            [
                ['1', '2', '3','4', '5'], ['1', '2', '3', '4'], ['1', '2', '3'],['1', '2', '3', '4'], ['1', '2', '3', '4', '6'],
                ['1', '2', '3'] //array strict intersection
            ],
            [
                ['0', '7', '3','4', '5'], ['1', '2', '3', '4'], ['1', '2', '3'],['1', '2', '3', '4'], ['1', '2', '3', '4', '9', '8'], ['1', '2', '3', '4', '7'],
                [] //array strict intersection
            ]
        ];
    }

    /**
     * Same as array_intersect_assoc but stop to compare after one difference
     * @dataProvider arrayIntersectProvider
     */
    public function testArray_intersect_assoc_strict() {

        $args = func_get_args();

        $expected = array_pop($args);

        $this->assertSame($expected, $this->callInaccessibleMethod('array_intersect_assoc_strict', $args));

    }

    /**
     * test getComonTracePart
     * @dataProvider tracesProvider
     * @depends testArray_intersect_assoc_strict
     */
    public function testGetCommonTracePart($mergedTraces, $minSize, $expectedStart, $expectedEnd) {

        $this->assertSame($expectedStart, $this->instance->getCommonTracePart($mergedTraces,true));
        $this->assertSame($expectedEnd, $this->instance->getCommonTracePart($mergedTraces,false));

    }

    public function callsProvider() {

        $fixtureTrace1 = //traces
            [
                ['trace' => ['1', '2', '3', '4', '5']],
                ['trace' => ['1', '2', '33', '4', '5']],
                ['trace' => ['1', '2', '3', '33', '4', '5']],
                ['trace' => ['1', '2', '4', '5']],
            ];
        $fixtureTrace2 = //traces
            [
                ['trace' => ['1', '2', '3', '4', '5']],
                ['trace' => ['1', '2', '33', '4', '5']],
                ['trace' => ['1', '2', '4', '5']],
            ];
        $fixtureTrace3 = //traces
            [
                ['trace' => ['1', '2', '3', '4', '5']],
                ['trace' => ['1', '2', '33', '4', '5']],
                ['trace' => ['1', '4', '5']],
            ];
        $fixtureTrace4 = //traces
            [
                ['trace' => ['1', '2', '33', '4', '5']],
                ['trace' => ['1', '2', '3', '33', '4', '5']],
                ['trace' => ['1', '2', '4', '5']],
            ];

        return
        [
            [
                [
                    $call1 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace1]]),
                    $call11 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace1]]),
                    $call2 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace2]]),
                    $call3 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace3]]),
                    $call33 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace3]]),
                    $call4 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace4]]),
                ],
                [
                    md5(json_encode($fixtureTrace1)) => ['count' => 2, 'trace' => $fixtureTrace1],
                    md5(json_encode($fixtureTrace2)) => ['count' => 1, 'trace' => $fixtureTrace2],
                    md5(json_encode($fixtureTrace3)) => ['count' => 2, 'trace' => $fixtureTrace3],
                    md5(json_encode($fixtureTrace4)) => ['count' => 1, 'trace' => $fixtureTrace4],
                ]
            ],
            [
                [
                    $call1 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace1]]),
                    $call11 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace1]]),
                    $call111 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace1]]),
                    $call2 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace2]]),
                    $call22 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace2]]),
                    $call3 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace3]]),
                    $call4 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace4]]),
                ],
                [
                    md5(json_encode($fixtureTrace1)) => ['count' => 3, 'trace' => $fixtureTrace1],
                    md5(json_encode($fixtureTrace2)) => ['count' => 2, 'trace' => $fixtureTrace2],
                    md5(json_encode($fixtureTrace3)) => ['count' => 1, 'trace' => $fixtureTrace3],
                    md5(json_encode($fixtureTrace4)) => ['count' => 1, 'trace' => $fixtureTrace4],
                ]
            ],
            [
                [
                    $call1 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace1]]),
                    $call11 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace1]]),
                    $call111 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace1]]),
                    $call1111 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace1]]),
                    $call11111 = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['getCallerTrace' => ['will' => $fixtureTrace1]]),
                ],
                [
                    md5(json_encode($fixtureTrace1)) => ['count' => 5, 'trace' => $fixtureTrace1],
                ]
            ]
        ];
    }
    /**
     * test getMergedTrace
     * @dataProvider callsProvider
     */
    public function testGetMergedTrace($calls, $expected) {

        $this->assertEquals($expected, $this->instance->getMergedTrace($calls));
    }


    /**
     * Provider getMethodCodeFromTrace
     * @return array
     */
    public function traceFileProvider() {

        $fixtureMethodSrc1 = <<<'FUNC'
    public function GetToken($index){
        //Some comment
        if(true) {
            return function ($b){ return $a * 2;};
        }
    }

FUNC;

        $fixtureMethodSrc2 = <<<'FUNC'
public function GetWord($index){
        //Some comment
        if(true)           {
            return        false;
}
    }

FUNC;
        $fixtureMethodSrc3 = <<<'FUNC'
    public function GetSyntax    (          $index          )              {
        //Some comment
        if     (     true    ) {
            return false;
        }
    }

FUNC;

        $fixtureSrc = <<<SRC
<?php

class SrcParser extends AbstractParserClass implements ParserInterface
{

    public function otherFunction() {
        return function(\$a) use (\$b) { return \$a *= \$b; };
    }

$fixtureMethodSrc1

$fixtureMethodSrc2
/***********************/





            $fixtureMethodSrc3

    public function lastMethod(\$params) {
        \$this->params = \$params;
    }
}
SRC;


        return
        [
            [
                $fixtureSrc, ['file' => 'vfs://tmp/Parser.php', 'line' => 10],['startOffset' => 9, 'src' => $fixtureMethodSrc1],
                $fixtureSrc, ['file' => 'vfs://tmp/Parser.php', 'line' => 20], ['startOffset' => 17, 'src' => $fixtureMethodSrc2],
                $fixtureSrc, ['file' => 'vfs://tmp/Parser.php', 'line' => 35], ['startOffset' => 30, 'src' => $fixtureMethodSrc3],
            ]
        ];
    }

    /**
     * test getMethodCodeFromTrace
     * @dataProvider traceFileProvider
     */
    public function testGetMethodCodeFromTrace($src, $trace, $expected) {
        $this->root = vfsStream::setup('tmp',777, ['Parser.php' => $src]);
        $this->assertSame($expected, $this->instance->getMethodCodeFromTrace($trace));
    }

    public function testGetUmlImgUrl() {

        if(!defined('ROOT_PATH')) {
            define('ROOT_PATH', '/var/www/');
        }

        $libraryPath = 'Tao/Library';
        $rootPath = ROOT_PATH . $libraryPath . '/';
        
        $fixtureMergedTrace =
        [
            [
                'trace' =>
                [
                    ['file' => $rootPath . 'file1.php', 'line' => 1, 'function' => 'function1'],
                    ['file' => $rootPath . 'file2.php', 'line' => 2, 'function' => 'function2'],
                    ['file' => $rootPath . 'file3.php', 'line' => 3, 'function' => 'function3'],
                    ['file' => $rootPath . 'file4.php', 'line' => 4, 'function' => 'function4'],

                    ['file' => $rootPath . 'file41.php', 'line' => 41, 'function' => 'function41'],
                    ['file' => $rootPath . 'file42.php', 'line' => 42, 'function' => 'function42'],

                    ['file' => $rootPath . 'file5.php', 'line' => 5, 'function' => 'function5'],
                    ['file' => $rootPath . 'file6.php', 'line' => 6, 'function' => 'function6'],
                ],
                'count' => 2
            ],
            [
                'trace' =>
                [
                    ['file' => $rootPath . 'file1.php', 'line' => 1, 'function' => 'function1'],
                    ['file' => $rootPath . 'file2.php', 'line' => 2, 'function' => 'function2'],
                    ['file' => $rootPath . 'file3.php', 'line' => 3, 'function' => 'function3'],
                    ['file' => $rootPath . 'file4.php', 'line' => 4, 'function' => 'function4'],

                    ['file' => $rootPath . 'file43.php', 'line' => 43, 'function' => 'function43'],
                    ['file' => $rootPath . 'file44.php', 'line' => 44, 'function' => 'function44'],

                    ['file' => $rootPath . 'file5.php', 'line' => 5, 'function' => 'function5'],
                    ['file' => $rootPath . 'file6.php', 'line' => 6, 'function' => 'function6'],
                ],
                'count' => 3
            ],
            [
                'trace' =>
                [
                    ['file' => $rootPath . 'file1.php', 'line' => 1, 'function' => 'function1'],
                    ['file' => $rootPath . 'file2.php', 'line' => 2, 'function' => 'function2'],
                    ['file' => $rootPath . 'file3.php', 'line' => 3, 'function' => 'function3'],
                    ['file' => $rootPath . 'file4.php', 'line' => 4, 'function' => 'function4'],
                    ['file' => $rootPath . 'file5.php', 'line' => 5, 'function' => 'function5'],
                    ['file' => $rootPath . 'file6.php', 'line' => 6, 'function' => 'function6'],
                ],
                'count' => 4
            ]
        ];

        $expectedStartTrace = '(' . $libraryPath . '\rfile4.php::4\rfunction4)';
        $expectedEndTrace = '(' . $libraryPath . '\rfile5.php::5\rfunction5)';

        $expected  = 'http://yuml.me/diagram/plain/activity/' .
            $expectedStartTrace . '-2>' .
            '(' . $libraryPath . '\rfile42.php::42\rfunction42)-2>' .
            '(' . $libraryPath . '\rfile41.php::41\rfunction41)-2>' .
            $expectedEndTrace . ',' .
            $expectedStartTrace . '-3>' .
            '(' . $libraryPath . '\rfile44.php::44\rfunction44)-3>' .
            '(' . $libraryPath . '\rfile43.php::43\rfunction43)-3>' .
            $expectedEndTrace . ',' .
            $expectedStartTrace . '-4>' .
            $expectedEndTrace
        ;

        $this->assertEquals($expected, $this->instance->getUmlImgUrl($fixtureMergedTrace, 4, 2,
            ['file' => $rootPath . 'file5.php', 'line' => 5, 'function' => 'function5'],
            ['file' => $rootPath . 'file4.php', 'line' => 4, 'function' => 'function4']
        ));
    }

    /**
     * Return the Scruffy representation of this trace
     * @param $trace
     *
     * @return string
     */
    protected function umlRenderTrace($trace) {
        $file = substr($trace['file'], strlen(ROOT_PATH));
        $explodedFile = explode('/', $file);
        $file = array_pop($explodedFile);
        $path = implode('/', $explodedFile);

        return '(' .  $path . '\r' . $file . '::' .  $trace['line'] . '\r' . $trace['function'] . ')';

    }
}