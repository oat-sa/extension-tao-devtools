<?php
/**
 * Method test case
 */

namespace TaoTest\Generis\Test\Monitor\Chunk;
use oat\taoDevTools\helper\PhpunitTestCase;

/**
 * Class MethodTest
 * @package Tao\Generis\Test\Monitor\Chunk
 * @group monitor
 */
class MethodTest extends PhpunitTestCase
{
    /**
     * @var \oat\taoDevTools\models\Monitor\Chunk\MethodChunk
     */
    protected $instance;

    public function setUp() {

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\MethodChunk', true);

    }

    /**
     * test default property value
     */
    public function testDefaultPropertyValue() {
        $this->assertEquals('undefined', $this->getInaccessiblePropertyValue('methodName'));
        $this->assertEquals([], $this->getInaccessiblePropertyValue('callHashes'));
    }

    /**
     * Constructor
     * @param oat\taoDevTools\models\Monitor\Chunk\InstanceChunk $instance
     * @param                            $methodName
     */
    public function test__construct() {

        $instanceMock = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\Call\AbstractContainer', true);
        $fixtureMethodName = 'MyMethod';

        $this->instance->__construct($instanceMock, $fixtureMethodName);

        $this->assertSame($instanceMock, $this->instance->getInstance());
        $this->assertEquals($fixtureMethodName, $this->instance->getMethodName());

    }

    /**
     * test appendCall
     */
    public function testAppendCallDuplicate() {
        $fixtureParams = ['param1' => 'value1', 'param2' => 'value2'];
        $fixtureHash = 'e7613e047876a84761546daf5fd9c3b6';

        $mockCall = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false,
            ['setDuplicated' => [], 'getHash' => ['will' => $fixtureHash]]
        );

        $mockOriginalCall = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false, ['setDuplicated' => []]);

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\MethodChunk', false,
            [
                'callFactory'       => ['with' => [$fixtureParams], 'will' => $mockCall],
                'addDuplicatedCall' => ['with' => [$mockOriginalCall]],
                'addCall'           => ['with' => [$mockCall]]
            ]
        );


        $this->setInaccessiblePropertyValue('callHashes', [$fixtureHash => $mockOriginalCall]);

        $this->assertSame($mockCall, $this->instance->appendCall($fixtureParams));

    }

    /**
     * test appendCall new
     */
    public function testAppendCallNew() {
        $fixtureParams = ['param1' => 'value1', 'param2' => 'value2'];
        $fixtureHash = 'e7613e047876a84761546daf5fd9c3b6';

        $mockCall = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false,
            ['setDuplicated' => ['expects' => $this->never()], 'getHash' => ['will' => $fixtureHash]]
        );

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\MethodChunk', false,
            [
                'callFactory'       => ['with' => [$fixtureParams], 'will' => $mockCall],
                'addDuplicatedCall' => ['expects' => $this->never()],
                'addCall'           => ['with' => [$mockCall]]
            ]
        );

        $this->assertSame($mockCall, $this->instance->appendCall($fixtureParams));
        $callHashes = $this->getInaccessiblePropertyValue('callHashes');

        $this->assertTrue(isset($callHashes[$fixtureHash]));
        $this->assertSame($mockCall, $callHashes[$fixtureHash]);
    }

    /**
     * test callFactory
     */
    public function testCallFactory() {

        $this->assertInstanceOf('oat\taoDevTools\models\Monitor\Chunk\CallChunk', $this->instance->callFactory(['param1' => 'value1']));

    }
}