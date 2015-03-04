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
class InstanceTest extends PhpunitTestCase
{
    /**
     * @var \oat\taoDevTools\models\Monitor\Chunk\InstanceChunk
     */
    protected $instance;

    public function setUp() {

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\InstanceChunk');
    }

    /**
     * test default property value
     */
    public function testDefaultPropertyValue() {
        $this->assertEquals('unknown', $this->getInaccessiblePropertyValue('id'));
        $this->assertEquals('n/a', $this->getInaccessiblePropertyValue('rdfUri'));
        $this->assertNull($this->getInaccessiblePropertyValue('target'));
    }

    /**
     * @return array
     */
    public function constructProvider() {
        $fixtureUri = 'my/test/uri';
        $mockClass = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\ClassChunk');
        $mockInstanceUri = $this->getMockFromArray('stdClass', false, ['getUri' => ['will' => $fixtureUri]]);
        $mockInstance = $this->getMockFromArray('stdClass');
        return
        [
            [$mockClass, $mockInstanceUri, $fixtureUri],
            [$mockClass, $mockInstance, 'n/a'],
        ];

    }

    /**
     * test __construct
     * @dataProvider constructProvider
     */
    public function test__construct( $class, $instance, $expectedUri) {
        $this->instance = new \oat\taoDevTools\models\Monitor\Chunk\InstanceChunk($class, $instance);
        $this->assertSame($instance, $this->getInaccessiblePropertyValue('target'));
        $this->assertSame($class, $this->instance->getClass());
        $this->assertEquals(spl_object_hash($instance), $this->instance->getId());
        $this->assertEquals($expectedUri, $this->instance->getRdfUri());
    }
}