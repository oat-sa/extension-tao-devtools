<?php
/**
 * AbstractContainer test case
 */

namespace TaoTest\Generis\Test\Monitor\Chunk\Call;

use oat\taoDevTools\helper\PhpunitTestCase;

class AbstractContainerTest extends PhpunitTestCase
{
    /**
     * @var oat\taoDevTools\models\Monitor\Chunk\CallChunk_AbstractContainer
     */
    protected $instance;

    public function setUp() {

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\Call\AbstractContainer', true);

    }

    /**
     * Return the linked parent chunk
     * @return common_monitor_AbstractChunkCallContainer
     */
    public function testGetParentChunk() {

        $fixture = new \stdClass();

        $this->setInaccessiblePropertyValue('parentChunk', $fixture);

        $this->assertSame($fixture, $this->instance->getParentChunk());
    }

    /**
     * Call provider
     * @return array
     */
    public function callProvider() {
        //oat\taoDevTools\models\Monitor\Chunk\CallChunk
        return
        [
            [
                $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false,
                [
                    'isDuplicated' => ['will' => true]
                ]), true, true
            ],
            [
                $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\CallChunk', false,
                    [
                        'isDuplicated' => ['will' => false]
                    ]), false, false
            ]
        ];
    }

    /**
     * test addCall
     * @param oat\taoDevTools\models\Monitor\Chunk\CallChunk $call
     * @dataProvider callProvider
     */
    public function testAddCall($call, $recursive, $isDuplicated) {

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\Call\AbstractContainer', true,
            [
                'getParentChunk' =>
                [
                    'expects' => $recursive ? $this->once() : $this->never(),
                    'will' => $recursive ?
                            $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\Call\AbstractContainer', true,
                            ['addCall' => ['with' => [$call]]]
                            ) : null,
                ]
            ]
        );

        $this->instance->addCall($call, $recursive);

        $this->assertContains($call, $this->instance->getCalls());
        if($isDuplicated) {
            $this->assertContains($call, $this->instance->getDuplicatedCalls());
        }

    }

    /**
     * test addDuplicatedCall
     * @param $call
     * @param $recursive
     * @param $isDuplicated
     * @dataProvider callProvider
     */
    public function testAddDuplicatedCall($call, $recursive, $isDuplicated) {

        $this->instance = $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\Call\AbstractContainer', true,
            [
                'getParentChunk' =>
                    [
                        'expects' => $recursive ? $this->once() : $this->never(),
                        'will' => $recursive ?
                                $this->getMockFromArray('oat\taoDevTools\models\Monitor\Chunk\Call\AbstractContainer', true,
                                    ['addDuplicatedCall' => ['with' => [$call]]]
                                ) : null,
                    ]
            ]
        );

        $this->instance->addDuplicatedCall($call, $recursive);

        $this->assertContains($call, $this->instance->getDuplicatedCalls());

    }

    /**
     * test getCalls
     */
    public function testGetCalls() {
        $fixture = [new \stdClass()];
        $this->setInaccessiblePropertyValue('calls', $fixture);

        $this->assertEquals($fixture, $this->instance->getCalls());
    }

    /**
     * test getDuplicatedCalls
     */
    public function testGetDuplicatedCalls() {
        $fixture = [new \stdClass()];
        $this->setInaccessiblePropertyValue('duplicatedCalls', $fixture);
        $this->assertEquals($fixture, $this->instance->getDuplicatedCalls());
    }


}