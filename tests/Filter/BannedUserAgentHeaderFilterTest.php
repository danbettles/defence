<?php

declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Filter;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use ThreeStreams\Defence\Envelope;
use ThreeStreams\Defence\Filter\AbstractFilter;
use ThreeStreams\Defence\Filter\BannedUserAgentHeaderFilter;
use ThreeStreams\Defence\Logger\NullLogger;
use ThreeStreams\Defence\Tests\TestsFactory\RequestFactory;

class BannedUserAgentHeaderFilterTest extends TestCase
{
    public function testIsAnAbstractfilter()
    {
        $this->assertTrue(\is_subclass_of(BannedUserAgentHeaderFilter::class, AbstractFilter::class));
    }

    public function providesRequestsFromBannedUserAgents(): array
    {
        $requestFactory = new RequestFactory();

        return [
            [
                true,
                $requestFactory->createWithHeader('User-Agent', 'python-requests/2.23.0'),
                [
                    '~python-requests/~',
                ],
            ],
            [
                true,
                $requestFactory->createWithHeader('User-Agent', 'python-requests/2.23.0'),
                [
                    '~foo~',
                    '~python-requests/~',
                ],
            ],
            [
                true,
                $requestFactory->createWithHeader('User-Agent', 'python-requests/2.23.0'),
                '~python-requests/~',
            ],
        ];
    }

    public function providesRequestsFromPermittedUserAgents(): array
    {
        $requestFactory = new RequestFactory();

        return [
            [
                false,
                $requestFactory->createWithHeader('User-Agent', 'Mozilla/5.0 ...'),
                [
                    '~python-requests/~',
                ],
            ],
            [
                false,
                $requestFactory->createWithHeader('User-Agent', 'Mozilla/5.0 ...'),
                '~python-requests/~',
            ],
        ];
    }

    public function providesRequests(): array
    {
        return \array_merge(
            $this->providesRequestsFromBannedUserAgents(),
            $this->providesRequestsFromPermittedUserAgents()
        );
    }

    /**
     * @dataProvider providesRequests
     */
    public function testInvokeReturnsTrueIfTheUserAgentIsBanned(bool $expected, Request $request, $selector)
    {
        $envelope = new Envelope($request, new NullLogger());
        $filter = new BannedUserAgentHeaderFilter($selector);

        $this->assertSame($expected, $filter($envelope));
    }

    /**
     * @dataProvider providesRequestsFromBannedUserAgents
     */
    public function testInvokeWillAddALogEntryIfTheRequestIsFromABannedUserAgent(
        bool $expected,
        Request $request,
        $selector
    ) {
        $completeEnvelope = new Envelope($request, new NullLogger());

        $filterMock = $this
            ->getMockBuilder(BannedUserAgentHeaderFilter::class)
            ->setConstructorArgs([
                $selector,
            ])
            ->onlyMethods(['envelopeAddLogEntry'])
            ->getMock()
        ;

        $filterMock
            ->expects($this->once())
            ->method('envelopeAddLogEntry')
            ->with($completeEnvelope, 'The request was made via a banned user agent.')
        ;

        $this->assertSame($expected, $filterMock($completeEnvelope));
    }

    /**
     * @dataProvider providesRequestsFromPermittedUserAgents
     */
    public function testInvokeWillNotAddALogEntryIfTheRequestIsFromAPermittedUserAgent(
        bool $expected,
        Request $request,
        $selector
    ) {
        $completeEnvelope = new Envelope($request, new NullLogger());

        $filterMock = $this
            ->getMockBuilder(BannedUserAgentHeaderFilter::class)
            ->setConstructorArgs([
                $selector,
            ])
            ->onlyMethods(['envelopeAddLogEntry'])
            ->getMock()
        ;

        $filterMock
            ->expects($this->never())
            ->method('envelopeAddLogEntry')
        ;

        $this->assertSame($expected, $filterMock($completeEnvelope));
    }

    public function providesInvalidSelectors(): array
    {
        return [
            [
                [],
            ],
            [
                '',
            ],
            [
                null,
            ],
            [
                123,
            ],
        ];
    }

    /**
     * @dataProvider providesInvalidSelectors
     */
    public function testThrowsAnExceptionIfTheSelectorIsInvalid($invalidSelector)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The selector is invalid.');

        new BannedUserAgentHeaderFilter($invalidSelector);
    }
}
