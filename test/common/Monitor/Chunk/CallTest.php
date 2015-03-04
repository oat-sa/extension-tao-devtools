<?php
/**
 * AbstractContainer test case
 */

namespace TaoTest\Generis\Test\Monitor\Chunk;
use oat\taoDevTools\helper\PhpunitTestCase;

/**
 * Class CallTest
 * @package Tao\Generis\Test\Monitor\Chunk
 * @group monitor
 */
class CallTest extends PhpunitTestCase
{
    /**
     * @var \oat\taoDevTools\models\Monitor\Chunk\CallChunk
     */
    protected $instance;

    public function setUp() {

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', true);

    }

    /**
     * Constructor
     * @param oat\taoDevTools\models\Monitor\Chunk\MethodChunk  $method
     * @param array $params
     */
    public function test__construct() {

        $fixtureMethod = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\MethodChunk', false, []);
        $fixtureParams = ['param1' => 'value1'];

        $this->instance->__construct($fixtureMethod, $fixtureParams);

        $this->assertSame($fixtureMethod, $this->instance->getMethod());
        $this->assertEquals($fixtureParams, $this->instance->getParams());

        if(function_exists('xdebug_get_function_stack')) {
            $this->assertNotEmpty($this->instance->getTrace());
        }
    }

    /**
     * test getHash
     */
    public function testGetHash() {

        $fixtureHash = 'e7613e047876a84761546daf5fd9c3b6';
        $fixtureParams = ['param1' => 'value1', 'param2' => 'value2'];
        $this->setInaccessiblePropertyValue('params', $fixtureParams);
        $this->assertEquals($fixtureHash, $this->instance->getHash());
    }

    /**
     * test is and get duplicated
     */
    public function testIsAndSetDuplicated() {

        $this->assertSame($this->instance, $this->instance->setDuplicated(true));
        $this->assertTrue($this->instance->isDuplicated());
    }

    /**
     * test getTrace
     */
    public function testGetTrace() {
        $this->setInaccessiblePropertyValue('trace', ['trace']);
        $this->assertEquals(['trace'], $this->instance->getTrace());
    }

    /**
     * trace provider
     */
    public function traceProvider() {
        return
        [
            [
                [//trace
                    ['trace' => '1'],
                    ['trace' => '2'],
                    ['trace' => '3'],
                    ['trace' => '4'],
                    ['trace' => '5'],
                ],
                [//caller trace
                    ['trace' => '1'],
                ],

            ],
            [
                [//trace
                    ['trace' => '1'],
                    ['trace' => '2'],
                    ['trace' => '3'],
                ],
                []

            ]
        ];
    }

    /**
     * test getCallerTrace
     * @dataProvider traceProvider
     */
    public function testGetCallerTrace($trace, $callerTrace) {

        $this->setInaccessiblePropertyValue('trace', $trace);

        $this->assertEquals($callerTrace, $this->instance->getCallerTrace());

    }

    /**
     * test getParams
     */
    public function getParams() {
        $fixtureParams = ['param1' => 'value1'];
        $this->setInaccessiblePropertyValue('params', $fixtureParams);
        $this->assertEquals($fixtureParams, $this->instance->getParams());
    }

}