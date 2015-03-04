<?php

namespace TaoTest\Generis\Test\Monitor;

use oat\taoDevTools\helper\PhpunitTestCase;
use oat\taoDevTools\models\Monitor\Monitor;

/**
 * Monitor test case
 * @group monitor
 */
class MonitorTest extends PHPUnitTestCase
{
    /**
     * @var \oat\taoDevTools\models\Monitor\Monitor
     */
    protected $instance;

    public function setUp() {

        $this->instance = new Monitor();

    }

    /**
     * Test default property value
     */
    public function testDefaultPropertyValue() {

        $this->assertFalse($this->getInaccessiblePropertyValue('enabled'));
        $this->assertEquals([], $this->getInaccessiblePropertyValue('adapters'));

    }

    /**
     * Configuration provider
     * @return array
     */
    public function configProvider() {

        $fixtureAdaptersConfig =
            [
                'test' => ['option1' => true]
            ];

        $fixtureConfig =
            [
                'enabled' => true,
                'adapters' => $fixtureAdaptersConfig
            ];

        return
        [
            [
                $fixtureConfig,
                [
                    'setAdapters' => ['with' => [$fixtureAdaptersConfig]],
                    'setEnabled'  => ['with' => [true]],
                ]
            ],
            [
                null,
                [
                    'setAdapters' => ['expects' => $this->never()],
                    'setEnabled'  => ['expects' => $this->never()],
                ]
            ]
        ];
    }

    /**
     * Test constructor
     * @dataProvider configProvider
     */
    public function test__construct($config, $mockConfig) {


        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Monitor',false, $mockConfig);

        $this->instance->__construct($config);

    }

    /**
     * test isEnabled
     */
    public function testSetIsEnabled() {
        $this->assertSame($this->instance,$this->instance->setEnabled(true));
        $this->assertTrue($this->instance->isEnabled());
    }

    /**
     * test setAdapters
     */
    public function testSetAdapters() {

        $adapterOptionFixture = ['option1' => true, 'option2' => false];
        $adaptersFixture =
            [
                $this->getMockForAbstractClass('oat\taoDevTools\models\Monitor\OutputAdapter\AbstractAdapter',[],'common_monitor_Adapter_Test',
                    true, true, true, ['setOption1', 'setOption2']),
                'test' => $adapterOptionFixture,
            ];

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Monitor', false,
            ['adapterFactory' => ['with' => ['test', $adapterOptionFixture]]]
        );

        $this->instance->setAdapters($adaptersFixture);
        $this->assertCount(2, $this->instance->getAdapters());
    }

    /**
     * Provider for adapterFactory
     * @return array
     */
    public function adapterFactoryProvider() {

        $stubCommonAdapter = $this->getMockForAbstractClass('oat\taoDevTools\models\Monitor\OutputAdapter\AbstractAdapter',[],'StubAdapter',
            true, true, true,['__construct']);
        $stubMyAdapter = $this->getMockForAbstractClass('oat\taoDevTools\models\Monitor\OutputAdapter\AbstractAdapter',[],'StubMyAdapter',
            true, true, true,['__construct']);

        return
        [
          ['unknown_adapter_class', ['options1' => true], 'Exception'],
          ['StubAdapter', ['options1' => true], false],
          ['StubMyAdapter', ['options1' => true], false],
        ];
    }

    /**
     * test adapterFactory
     * @param $name
     * @param $config
     * @param $exception
     * @dataProvider adapterFactoryProvider
     */
    public function testAdapterFactory($name, $config, $exception) {
        if($exception) {
            $this->setExpectedException($exception);
        }

        $this->instance->adapterFactory($name, $config);
    }

    /**
     * test getRequest
     */
    public function testGetRequest() {

        $request = $this->instance->getRequest();
        $this->assertInstanceOf('oat\taoDevTools\models\Monitor\Chunk\RequestChunk', $request);
        $this->assertSame($request, $this->instance->getRequest());
    }

    /**
     * test append when enabled
     */
    public function testAppendWhenEnabled() {

        $fixtureParams = ['param1' => 'value1'];
        $fixtureMethodName = 'myMethod';

        $mockCall = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false);

        $mockMethod = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\MethodChunk', false,
            ['appendCall' => ['with' => [$fixtureParams], 'will' => $mockCall]]);

        $mockInstance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\InstanceChunk', false,
            ['getMethod' => ['with' => [$fixtureMethodName], 'will' => $mockMethod]]);

        $mockClass = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\ClassChunk', false,
            ['getInstance' => ['with' => [$this], 'will' => $mockInstance]]);

        $mockRequest = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\RequestChunk', false,
            ['getClass' => ['with' => [get_class($this)], 'will' => $mockClass]]);

        $adaptersMock =
            [
                $this->getMockFromArray('oat\taoDevTools\models\Monitor\OutputAdapter\AbstractAdapter', true,['appendCall' => ['with' => [$mockCall]]]),
                $this->getMockFromArray('oat\taoDevTools\models\Monitor\OutputAdapter\AbstractAdapter', true,['appendCall' => ['with' => [$mockCall]]]),
            ];

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Monitor', false,
            [
                'getRequest' => ['will' => $mockRequest ],
                'getAdapters' => ['will' => $adaptersMock ],
                'isEnabled' => ['will' => true]
            ]);

        $this->instance->append($this, $fixtureMethodName, $fixtureParams);
    }

    /**
     * test append when disabled
     */
    public function testAppendWhenDisabled() {

        $fixtureParams = ['param1' => 'value1'];
        $fixtureMethodName = 'myMethod';

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Monitor', false,
            [
                'getRequest' => ['expects' => $this->never() ],
                'getAdapters' => ['expects' => $this->never() ],
                'isEnabled' => ['will' => false]
            ]);

        $this->instance->append($this, $fixtureMethodName, $fixtureParams);
    }


    /**
     * __destruct provider
     * @return array
     */
    public function destructProvider() {
        return
        [
            [
                true,
                $request = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\RequestChunk'),
                [
                    $this->getMockFromArray('oat\taoDevTools\models\Monitor\OutputAdapter\AbstractAdapter', true, ['endMonitoring' => ['with' => [$request]]]),
                    $this->getMockFromArray('oat\taoDevTools\models\Monitor\OutputAdapter\AbstractAdapter', true, ['endMonitoring' => ['with' => [$request]]]),
                ]
            ],
            [
                false,
                $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\RequestChunk'),
                [
                    $this->getMockFromArray('oat\taoDevTools\models\Monitor\OutputAdapter\AbstractAdapter', true, ['endMonitoring' => ['expects' => $this->never()]]),
                    $this->getMockFromArray('oat\taoDevTools\models\Monitor\OutputAdapter\AbstractAdapter', true, ['endMonitoring' => ['expects' => $this->never()]]),
                ]
            ]
        ];
    }

    /**
     * test __destruct
     * @param $enabled
     * @param $adapters
     * @dataProvider destructProvider
     */
    public function test__destruct($enabled, $request, $adapters) {

        $this->instance->setEnabled($enabled);
        $this->setInaccessiblePropertyValue('request', $request);
        $this->instance->setAdapters($adapters);

        $this->instance = null; //Here __destruct will be called by PHP
        //If you call directly __destruct the mockAdapter will raise an error
        //because the endMonitoring method will be called twice. 1 time here
        //and one time when the PHP destruct the Monitor object

    }
}