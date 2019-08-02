<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use ThreeStreams\Gestalt\SimpleFilterChain;
use ThreeStreams\Defence\Envelope;
use ThreeStreams\Defence\Logger;
use ThreeStreams\Defence\Defence;
use ThreeStreams\Defence\Handler\HandlerInterface;
use ThreeStreams\Defence\Filter\FilterInterface;

class DefenceTest extends TestCase
{
    private function createEnvelope(): Envelope
    {
        return new Envelope(Request::createFromGlobals(), new Logger());
    }

    private function createHandlerMock()
    {
        return $this
            ->getMockBuilder(HandlerInterface::class)
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

        $filterChainMock = $this
            ->getMockBuilder(SimpleFilterChain::class)
            ->setMethods(['execute'])
            ->getMock()
        ;

        $filterChainMock
            ->expects($this->once())
            ->method('execute')
            ->with($envelope, true)  //Pass the envelope to each filter, break if a filter returns `true`.
            ->willReturn(true)  //Simulate request is suspicious.
        ;

        $handlerMock = $this
            ->getMockBuilder(HandlerInterface::class)
            ->setMethods(['__invoke'])
            ->getMock()
        ;

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

        $filterChainMock = $this
            ->getMockBuilder(SimpleFilterChain::class)
            ->setMethods(['execute'])
            ->getMock()
        ;

        $filterChainMock
            ->expects($this->once())
            ->method('execute')
            ->with($envelope, true)  //Pass the envelope to each filter, break if a filter returns `true`.
            ->willReturn(false)  //Simulate request is _not_ suspicious.
        ;

        $handlerMock = $this
            ->getMockBuilder(HandlerInterface::class)
            ->setMethods(['__invoke'])
            ->getMock()
        ;

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

        $falseFilterMock = $this
            ->getMockBuilder(FilterInterface::class)
            ->setMethods(['__invoke'])
            ->getMock()
        ;

        $falseFilterMock
            ->expects($this->once())  //Yes, the filter will be called.
            ->method('__invoke')
            ->with($envelope)
            ->willReturn(false)  //No, the request looks okay.
        ;

        $trueFilterMock = $this
            ->getMockBuilder(FilterInterface::class)
            ->setMethods(['__invoke'])
            ->getMock()
        ;

        $trueFilterMock
            ->expects($this->once())  //Yes, the filter will be called.
            ->method('__invoke')
            ->with($envelope)
            ->willReturn(true)  //Yes, the request looks suspicious.
        ;

        $neverFilterMock = $this
            ->getMockBuilder(FilterInterface::class)
            ->setMethods(['__invoke'])
            ->getMock()
        ;

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
