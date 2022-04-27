<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Filter;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use DanBettles\Defence\Filter\SuspiciousUserAgentHeaderFilter;
use DanBettles\Defence\Filter\AbstractFilter;
use DanBettles\Defence\Logger\NullLogger;
use DanBettles\Defence\Envelope;
use DanBettles\Defence\Tests\TestsFactory\RequestFactory;

class SuspiciousUserAgentHeaderFilterTest extends TestCase
{
    public function testIsAnAbstractfilter()
    {
        $this->assertTrue(is_subclass_of(SuspiciousUserAgentHeaderFilter::class, AbstractFilter::class));
    }

    public function providesRequestsWithSuspiciousUserAgentHeader(): array
    {
        $requestFactory = new RequestFactory();

        return [[
            true,
            $requestFactory->createWithHeader('User-Agent', ''),
        ], [
            false,
            $requestFactory->createWithHeader('User-Agent', 'Something'),
        ], [
            true,
            $requestFactory->createWithHeader('user-agent', ''),
        ], [
            false,
            $requestFactory->createWithHeader('user-agent', 'Something'),
        ], [
            true,
            $requestFactory->createWithHeader('user-agent', ' '),
        ], [
            true,
            $requestFactory->createWithHeader('user-agent', '   '),  //Much whitespace.
        ], [
            true,
            Request::createFromGlobals(),  //No `User-Agent` header at all.
        ], [
            true,
            $requestFactory->createWithHeader('User-Agent', '-'),
        ], [
            true,
            $requestFactory->createWithHeader('User-Agent', ' - '),
        ]];
    }

    /**
     * @dataProvider providesRequestsWithSuspiciousUserAgentHeader
     */
    public function testInvokeReturnsTrueIfTheUserAgentHeaderIsSuspicious($expected, $request)
    {
        $envelope = new Envelope($request, new NullLogger());
        $filter = new SuspiciousUserAgentHeaderFilter();

        $this->assertSame($expected, $filter($envelope));
    }

    public function testInvokeWillAddALogEntryIfTheRequestIsSuspicious()
    {
        $completeEnvelope = new Envelope(
            (new RequestFactory())->createWithHeader('User-Agent', ''),  //Existent, but blank, UA header.
            new NullLogger()
        );

        $filterMock = $this
            ->getMockBuilder(SuspiciousUserAgentHeaderFilter::class)
            ->onlyMethods(['envelopeAddLogEntry'])
            ->getMock()
        ;

        $filterMock
            ->expects($this->once())
            ->method('envelopeAddLogEntry')
            ->with($completeEnvelope, 'The request has a suspicious UA string.')
        ;

        $this->assertTrue($filterMock($completeEnvelope));

        $incompleteEnvelope = new Envelope(
            Request::createFromGlobals(),  //No UA header.
            new NullLogger()
        );

        $filterMock = $this
            ->getMockBuilder(SuspiciousUserAgentHeaderFilter::class)
            ->onlyMethods(['envelopeAddLogEntry'])
            ->getMock()
        ;

        $filterMock
            ->expects($this->once())
            ->method('envelopeAddLogEntry')
            ->with($incompleteEnvelope, 'The request has no UA string.')
        ;

        $this->assertTrue($filterMock($incompleteEnvelope));
    }
}
