<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Unit\Spryker\Zed\NewRelic\Communication\Plugin;

use Spryker\Shared\Library\System;
use Spryker\Shared\NewRelic\NewRelicApi;
use Spryker\Zed\Kernel\AbstractFactory;
use Spryker\Zed\NewRelic\Communication\Plugin\NewRelicConsolePlugin;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\Output;

/**
 * @group Unit
 * @group Spryker
 * @group Zed
 * @group NewRelic
 * @group Communication
 * @group Plugin
 * @group NewRelicConsolePluginTest
 */
class NewRelicConsolePluginTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @return void
     */
    public function testOnConsoleTerminateWithArgumentAndOptionsArray()
    {
        $commandMock = $this->getCommandMock();
        $inputMock = $this->getInputMock();
        $outputMock = $this->getOutputMock();
        $event = new ConsoleTerminateEvent($commandMock, $inputMock, $outputMock, 0);

        $newRelicConsolePluginMock = $this->getNewRelicConsolePluginMock();
        $newRelicConsolePluginMock->onConsoleTerminate($event);
    }

    /**
     * @return void
     */
    public function testGetSubscribedEventsShouldReturnArray()
    {
        $this->assertInternalType('array', NewRelicConsolePlugin::getSubscribedEvents());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Console\Command\Command
     */
    protected function getCommandMock()
    {
        $commandMock = $this->getMockBuilder(Command::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $commandMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Console\Input\InputInterface
     */
    protected function getInputMock()
    {
        $inputMock = $this->getMockBuilder(ArrayInput::class)
            ->setMethods(['getArguments', 'getOptions'])
            ->disableOriginalConstructor()
            ->getMock();

        $testData = [
            'foo' => 'bar',
            'baz' => [
                'zip' => 'zap'
            ]
        ];
        $inputMock->method('getArguments')->willReturn($testData);
        $inputMock->method('getOptions')->willReturn($testData);

        return $inputMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Console\Output\Output
     */
    protected function getOutputMock()
    {
        $outputMock = $this->getMockBuilder(Output::class)->getMock();

        return $outputMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Spryker\Zed\NewRelic\Communication\Plugin\NewRelicConsolePlugin
     */
    protected function getNewRelicConsolePluginMock()
    {
        $newRelicConsolePluginMock = $this->getMockBuilder(NewRelicConsolePlugin::class)
            ->setMethods(['getFactory'])
            ->getMock();

        $factoryMock = $this->getFactoryMock();
        $newRelicConsolePluginMock->method('getFactory')->willReturn($factoryMock);

        return $newRelicConsolePluginMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Spryker\Zed\Kernel\AbstractFactory
     */
    protected function getFactoryMock()
    {
        $factoryMock = $this->getMockBuilder(AbstractFactory::class)
            ->setMethods(['getNewRelicApi', 'provideExternalDependencies', 'injectExternalDependencies', 'getSystem'])
            ->getMock();

        $newRelicApiMock = $this->getNewRelicApiMock();
        $factoryMock->method('getNewRelicApi')->willReturn($newRelicApiMock);

        $factoryMock->method('getSystem')->willReturn(new System());

        return $factoryMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Spryker\Shared\NewRelic\NewRelicApi
     */
    protected function getNewRelicApiMock()
    {
        $newRelicApiMock = $this->getMockBuilder(NewRelicApi::class)
            ->setMethods(['addCustomParameter'])
            ->getMock();

        $newRelicApiMock->expects($this->exactly(5))->method('addCustomParameter')->willReturnCallback(function ($key, $value) {
            if (is_array($value)) {
                $this->fail('Input value of addCustomParameter() is not allowed to be an array');
            }
        });

        return $newRelicApiMock;
    }

}
