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
class RequestTest extends PhpunitTestCase
{
    /**
     * @var \oat\taoDevTools\models\Monitor\Chunk\RequestChunk
     */
    protected $instance;

    public function setUp() {

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\RequestChunk', true);

    }


    /**
     * test Constructor with uri
     */
    public function test__constructWithUri() {

        $fixtureUri = 'http://my/test/uri';
        $_SERVER['REQUEST_URI'] = $fixtureUri;

        $this->instance->__construct();
        $this->assertEquals($fixtureUri, $this->instance->getUri());


    }

    /**
     * test Constructor with uri
     */
    public function test__constructWithoutUri() {

        unset($_SERVER['REQUEST_URI']);

        $this->instance->__construct();
        $this->assertEquals('n/a', $this->instance->getUri());

    }

    /**
     * Return a chunk class based of the classname
     * @param $className
     *
     * @return oat\taoDevTools\models\Monitor\Chunk\ClassChunk
     */
    public function testGetClassNotExists() {

        $fixtureClassName = 'My\Class\Name';
        $mockClass = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\ClassChunk');

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\RequestChunk', false,
        [
            'classFactory' =>
            [
                'with' => [$fixtureClassName],
                'will' => $mockClass
            ]
        ]);


        $this->assertSame($mockClass, $this->instance->getClass($fixtureClassName));
        $classes = $this->instance->getClasses();
        $this->assertTrue(isset($classes[$fixtureClassName]) && ($classes[$fixtureClassName] === $mockClass));

    }

    /**
     * test classFactory
     */
    public function testClassFactory() {

        $this->assertInstanceOf('oat\taoDevTools\models\Monitor\Chunk\ClassChunk', $this->instance->classFactory('My\Class\Name'));
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