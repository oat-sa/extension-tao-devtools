<?php
/**
 * ContainerTrait test case
 */

namespace TaoTest\Generis\Test\Monitor\Chunk\Method;
use MyProject\Proxies\__CG__\stdClass;
use oat\taoDevTools\helper\PhpunitTestCase;

/**
 * Class ContainerTraitTest
 * @package TaoTest\Generis\Test\Monitor\Chunk\Method
 * @group monitor
 */
class ContainerTraitTest extends PhpunitTestCase
{

    public function setUP() {
        $this->instance = $this->getMockForTrait('oat\taoDevTools\models\Monitor\Chunk\Method\ContainerTrait',[]);
    }

    protected $methods = [];

    /**
     * Provide getMethod params
     * @return array
     */
    public function methodProvider() {

        $mockMethod = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\MethodChunk');
        return
        [
            ['myMethod', null           , ['myMethod' => $mockMethod]   , $mockMethod   ],
            ['myMethod', new \stdClass(), []                            , $mockMethod   ],
            ['myMethod', null           , []                            , $mockMethod   ],
        ];
    }

    /**
     * test getMethod
     * @param $methodName
     * @param $link
     * @param $methodsConfig
     * @param $expected
     * @dataProvider methodProvider
     */
    public function testGetMethod($methodName, $link, $methodsConfig, $expected) {


        $this->setInaccessiblePropertyValue('methods', $methodsConfig);

        if(!isset($methodsConfig[$methodName])) {
            $this->instance = $this->getMockForTrait('oat\taoDevTools\models\Monitor\Chunk\Method\ContainerTrait',[],'MockTrait',true, true, true, ['methodFactory']);
            $this->instance
                ->expects($this->once())
                ->method('methodFactory')
                ->with($methodName, is_null($link) ? $this->instance : $link)
                ->will($this->returnValue($expected));
        }

        $this->assertSame($expected, $this->instance->getMethod($methodName, $link));

        if(!isset($methodsConfig[$methodName])) {
            $methods = $this->getInaccessiblePropertyValue('methods');
            $this->assertTrue(isset($methods[$methodName]) && ($methods[$methodName] == $expected));
        }
    }

    /**
     * test methodFactory
     */
    public function testMethodFactory() {

        $this->assertInstanceOf('oat\taoDevTools\models\Monitor\Chunk\MethodChunk', $this->instance->methodFactory('myMethod', new \stdClass()));
    }

    /**
     * test getMethods
     */
    public function testGetMethods() {

        $fixtureMethods = ['myMethod' => new \stdClass()];
        $this->setInaccessiblePropertyValue('methods', $fixtureMethods);

        $this->assertEquals($fixtureMethods, $this->instance->getMethods());

    }


}
