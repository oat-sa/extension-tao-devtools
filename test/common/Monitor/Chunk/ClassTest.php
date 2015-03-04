<?php
/**
 * AbstractContainer test case
 */

namespace TaoTest\Generis\Test\Monitor\Chunk;
use MyProject\Proxies\__CG__\stdClass;
use oat\taoDevTools\helper\PhpunitTestCase;
use oat\taoDevTools\models\Monitor\Chunk\ClassChunk;

/**
 * Class CallTest
 * @package Tao\Generis\Test\Monitor\Chunk
 * @group monitor
 */
class ClassTest extends PhpunitTestCase
{
    /**
     * @var \oat\taoDevTools\models\Monitor\Chunk\ClassChunk
     */
    protected $instance;

    public function setUp() {

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\ClassChunk', true);

    }

    /**
     * Constructor
     * @param oat\taoDevTools\models\Monitor\Chunk\MethodChunk  $method
     * @param array $params
     */
    public function test__construct() {

        $mockRequest = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\RequestChunk');
        $fixtureClassName = 'My\\Class\\Name';
        $this->instance = new ClassChunk($mockRequest, $fixtureClassName);

        $this->assertSame($mockRequest, $this->instance->getRequest());
        $this->assertEquals($fixtureClassName, $this->instance->getClassName());
    }

    /**
     * test getInstance
     */
    public function testGetInstanceNotExists() {

        $mockInstance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\InstanceChunk');
        $mockTarget = new \stdClass();
        $fixtureId = spl_object_hash($mockTarget);

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\ClassChunk', false,
        [
            'instanceFactory' =>
            [
                'with' => [$mockTarget],
                'will' => $mockInstance
            ]
        ]);

        $this->assertSame($mockInstance, $this->instance->getInstance($mockTarget));
        $instances = $this->instance->getInstances();
        $this->assertTrue(isset($instances[$fixtureId]) && ($instances[$fixtureId] == $mockInstance));

    }

    /**
     * test getInstance
     */
    public function testGetInstanceExists() {

        $mockInstance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\InstanceChunk');
        $mockTarget = new \stdClass();
        $fixtureId = spl_object_hash($mockTarget);


        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\ClassChunk', false,
            ['instanceFactory' => ['expects' => $this->never()]]
        );

        $this->setInaccessiblePropertyValue('instances', [$fixtureId => $mockInstance]);
        $this->assertSame($mockInstance, $this->instance->getInstance($mockTarget));

    }

    /**
     * test getInstances
     */
    public function testGetInstances() {
        $fixtureInstances = ['id1' => new \stdClass()];
        $this->setInaccessiblePropertyValue('instances', $fixtureInstances);
        $this->assertSame($fixtureInstances, $this->instance->getInstances());
    }

    /**
     * test instanceFactory
     */
    public function testInstanceFactory() {

        $this->assertInstanceOf('oat\taoDevTools\models\Monitor\Chunk\InstanceChunk', $this->instance->instanceFactory($this));

    }

    /**
     * test addCall
     */
    public function testAddCall() {

        $fixtureMethodName = 'myMethod';
        $mockInstance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\InstanceChunk');

        $mockMethod = $this->getMock('oat\taoDevTools\models\Monitor\Chunk\MethodChunk', ['addCall', 'getMethodName', 'getInstance'],[new \stdClass(), $fixtureMethodName]);

        $mockCall = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false,
            [
                'getMethod' => ['expects' => $this->exactly(2), 'will' => $mockMethod]
            ]);

        $mockMethod->expects($this->once())
            ->method('addCall')
            ->with($mockCall, false)
        ;
        $mockMethod->expects($this->once())
            ->method('getMethodName')
            ->willReturn($fixtureMethodName)
        ;
        $mockMethod->expects($this->once())
            ->method('getInstance')
            ->willReturn($mockInstance)
        ;

        //oat\taoDevTools\models\Monitor\Chunk\CallChunk $call, $recursive = true

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\ClassChunk', false,
            [
                'getMethod' =>
                    [
                        'with' => [$fixtureMethodName, $mockInstance],
                        'will' => $mockMethod
                    ]
            ]);

        $this->instance->addCall($mockCall);
    }

    /**
     * test addDuplicatedCall
     */
    public function testAddDuplicatedCall() {

        $fixtureMethodName = 'myMethod';
        $mockInstance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\InstanceChunk');

        $mockMethod = $this->getMock('oat\taoDevTools\models\Monitor\Chunk\MethodChunk', ['addDuplicatedCall', 'getMethodName', 'getInstance'],[new \stdClass(), $fixtureMethodName]);

        $mockCall = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false,
            [
                'getMethod' => ['expects' => $this->exactly(2), 'will' => $mockMethod]
            ]);

        $mockMethod->expects($this->once())
            ->method('addDuplicatedCall')
            ->with($mockCall, false)
        ;
        $mockMethod->expects($this->once())
            ->method('getMethodName')
            ->willReturn($fixtureMethodName)
        ;
        $mockMethod->expects($this->once())
            ->method('getInstance')
            ->willReturn($mockInstance)
        ;

        //oat\taoDevTools\models\Monitor\Chunk\CallChunk $call, $recursive = true

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\ClassChunk', false,
            [
                'getMethod' =>
                    [
                        'with' => [$fixtureMethodName, $mockInstance],
                        'will' => $mockMethod
                    ]
            ]);

        $this->instance->addDuplicatedCall($mockCall);
    }


}