<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests;

use DanBettles\Defence\Defence;
use DanBettles\Defence\Envelope;
use DanBettles\Defence\Filter\FilterInterface;
use DanBettles\Defence\Handler\HandlerInterface;
use DanBettles\Defence\Logger\NullLogger;
use DanBettles\Gestalt\SimpleFilterChain;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class DefenceTest extends TestCase
{
    private function createEnvelope(): Envelope
    {
        return new Envelope(Request::createFromGlobals(), new NullLogger());
    }

    /** @return MockObject|HandlerInterface */
    private function createHandlerMock()
    {
        return $this
            ->getMockBuilder(HandlerInterface::class)
            ->getMock()
        ;
    }

    private function createFilterMock()
    {
        return $this
            ->getMockBuilder(FilterInterface::class)
            ->getMock()
        ;
    }

    public function testConstructor()
    {
        $filterChain = new SimpleFilterChain([]);
        $handlerMock = $this->createHandlerMock();

        $defence = new Defence($filterChain, $handlerMock);

        $this->assertSame($filterChain, $defence->getFilterChain());
        $this->assertSame($handlerMock, $defence->getHandler());
    }

    public function testExecuteExecutesTheHandlerIfTheRequestIsSuspicious()
    {
        $envelope = $this->createEnvelope();

        /** @var MockObject|SimpleFilterChain */
        $filterChainMock = $this
            ->getMockBuilder(SimpleFilterChain::class)
            ->onlyMethods(['execute'])
            ->getMock()
        ;

        $filterChainMock
            ->expects($this->once())
            ->method('execute')
            ->with($envelope, true)  //Pass the envelope to each filter, break if a filter returns `true`.
            ->willReturn(true)  //Simulate request is suspicious.
        ;

        $handlerMock = $this->createHandlerMock();

        $handlerMock
            ->expects($this->once())
            ->method('__invoke')
            ->with($envelope)
        ;

        $defence = new Defence($filterChainMock, $handlerMock);
        $defence->execute($envelope);
    }

    public function testExecuteWillNotExecuteTheHandlerIfTheRequestIsNotSuspicious()
    {
        $envelope = $this->createEnvelope();

        /** @var MockObject|SimpleFilterChain */
        $filterChainMock = $this
            ->getMockBuilder(SimpleFilterChain::class)
            ->onlyMethods(['execute'])
            ->getMock()
        ;

        $filterChainMock
            ->expects($this->once())
            ->method('execute')
            ->with($envelope, true)  //Pass the envelope to each filter, break if a filter returns `true`.
            ->willReturn(false)  //Simulate request is _not_ suspicious.
        ;

        $handlerMock = $this->createHandlerMock();

        $handlerMock
            ->expects($this->never())  //The handler must never be called if the request is not suspicious.
            ->method('__invoke')
        ;

        $defence = new Defence($filterChainMock, $handlerMock);
        $defence->execute($envelope);
    }

    public function testEachFilterInTheFilterChainIsInvoked()
    {
        $envelope = $this->createEnvelope();

        $falseFilterMock = $this->createFilterMock();

        $falseFilterMock
            ->expects($this->once())  //Yes, the filter will be called.
            ->method('__invoke')
            ->with($envelope)
            ->willReturn(false)  //No, the request looks okay.
        ;

        $trueFilterMock = $this->createFilterMock();

        $trueFilterMock
            ->expects($this->once())  //Yes, the filter will be called.
            ->method('__invoke')
            ->with($envelope)
            ->willReturn(true)  //Yes, the request looks suspicious.
        ;

        $neverFilterMock = $this->createFilterMock();

        $neverFilterMock
            ->expects($this->never())  //No, the filter won't be called -- it'll never be reached.
            ->method('__invoke')
        ;

        $filterChain = new SimpleFilterChain([
            $falseFilterMock,
            $trueFilterMock,
            $neverFilterMock,
        ]);

        $handlerMock = $this->createHandlerMock();

        $defence = new Defence($filterChain, $handlerMock);
        $defence->execute($envelope);
    }
}
