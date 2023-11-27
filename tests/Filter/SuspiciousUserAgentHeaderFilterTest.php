<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Filter;

use Symfony\Component\HttpFoundation\Request;
use DanBettles\Defence\Filter\SuspiciousUserAgentHeaderFilter;
use DanBettles\Defence\Filter\AbstractFilter;
use DanBettles\Defence\Logger\NullLogger;
use DanBettles\Defence\Envelope;
use DanBettles\Defence\Tests\AbstractTestCase;

use const false;
use const true;

class SuspiciousUserAgentHeaderFilterTest extends AbstractTestCase
{
    public function testIsAFilter(): void
    {
        $this->assertSubclassOf(AbstractFilter::class, SuspiciousUserAgentHeaderFilter::class);
    }

    /** @return array<mixed[]> */
    public function providesRequestsWithSuspiciousUserAgentHeader(): array
    {
        $requestFactory = $this->getRequestFactory();

        return [[
            true,
            $requestFactory->createWithHeaders(['User-Agent' => '']),
        ], [
            false,
            $requestFactory->createWithHeaders(['User-Agent' => 'Something']),
        ], [
            true,
            $requestFactory->createWithHeaders(['user-agent' => '']),
        ], [
            false,
            $requestFactory->createWithHeaders(['user-agent' => 'Something']),
        ], [
            true,
            $requestFactory->createWithHeaders(['user-agent' => ' ']),
        ], [
            true,
            $requestFactory->createWithHeaders(['user-agent' => '   ']),  //Much whitespace.
        ], [
            true,
            Request::createFromGlobals(),  //No `User-Agent` header at all.
        ], [
            true,
            $requestFactory->createWithHeaders(['User-Agent' => '-']),
        ], [
            true,
            $requestFactory->createWithHeaders(['User-Agent' => ' - ']),
        ]];
    }

    /** @dataProvider providesRequestsWithSuspiciousUserAgentHeader */
    public function testInvokeReturnsTrueIfTheUserAgentHeaderIsSuspicious(bool $expected, Request $request): void
    {
        $envelope = new Envelope($request, new NullLogger());
        $filter = new SuspiciousUserAgentHeaderFilter();

        $this->assertSame($expected, $filter($envelope));
    }

    public function testInvokeWillAddALogEntryIfTheRequestIsSuspicious(): void
    {
        $completeEnvelope = new Envelope(
            $this->getRequestFactory()->createWithHeaders(['User-Agent' => '']),  // Existent, but blank, UA header
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
