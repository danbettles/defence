<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Filter;

use DanBettles\Defence\Envelope;
use DanBettles\Defence\Filter\AbstractFilter;
use DanBettles\Defence\Filter\BannedUserAgentHeaderFilter;
use DanBettles\Defence\Logger\NullLogger;
use DanBettles\Defence\Tests\AbstractTestCase;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

use function array_merge;

use const false;
use const null;
use const true;

/**
 * @phpstan-import-type Selector from BannedUserAgentHeaderFilter
 */
class BannedUserAgentHeaderFilterTest extends AbstractTestCase
{
    public function testIsAFilter(): void
    {
        $this->assertSubclassOf(AbstractFilter::class, BannedUserAgentHeaderFilter::class);
    }

    /** @return array<mixed[]> */
    public function providesRequestsFromBannedUserAgents(): array
    {
        $requestFactory = $this->getRequestFactory();

        $requestByPythonRequests = $requestFactory->createWithHeaders(['User-Agent' => 'python-requests/2.23.0']);

        return [
            [
                true,
                $requestByPythonRequests,
                [
                    '~python-requests/~',
                ],
            ],
            [
                true,
                $requestByPythonRequests,
                [
                    '~foo~',
                    '~python-requests/~',
                ],
            ],
            [
                true,
                $requestByPythonRequests,
                '~python-requests/~',
            ],
        ];
    }

    /** @return array<mixed[]> */
    public function providesRequestsFromPermittedUserAgents(): array
    {
        $requestFactory = $this->getRequestFactory();

        $requestByMozilla = $requestFactory->createWithHeaders(['User-Agent' => 'Mozilla/5.0 ...']);
        $requestByUnknown = $requestFactory->createGet();

        return [
            [
                false,
                $requestByMozilla,
                [
                    '~python-requests/~',
                ],
            ],
            [
                false,
                $requestByMozilla,
                '~python-requests/~',
            ],
            // "Not suspicious *in this context*, no".  The request is suspicious because it has no UA, but the UA is
            // not blacklisted.
            [
                false,
                $requestByUnknown,
                '~python-requests/~',
            ],
        ];
    }

    /** @return array<mixed[]> */
    public function providesRequests(): array
    {
        return array_merge(
            $this->providesRequestsFromBannedUserAgents(),
            $this->providesRequestsFromPermittedUserAgents()
        );
    }

    /**
     * @dataProvider providesRequests
     * @phpstan-param Selector $selector
     */
    public function testInvokeReturnsTrueIfTheUserAgentIsBanned(
        bool $expected,
        Request $request,
        $selector
    ): void {
        $envelope = new Envelope($request, new NullLogger());
        $filter = new BannedUserAgentHeaderFilter($selector);

        $this->assertSame($expected, $filter($envelope));
    }

    /**
     * @dataProvider providesRequestsFromBannedUserAgents
     * @phpstan-param Selector $selector
     */
    public function testInvokeWillAddALogEntryIfTheRequestIsFromABannedUserAgent(
        bool $expected,
        Request $request,
        $selector
    ): void {
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
     * @phpstan-param Selector $selector
     */
    public function testInvokeWillNotAddALogEntryIfTheRequestIsFromAPermittedUserAgent(
        bool $expected,
        Request $request,
        $selector
    ): void {
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

    /** @return array<mixed[]> */
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
     * @param mixed $invalidSelector
     */
    public function testThrowsAnExceptionIfTheSelectorIsInvalid($invalidSelector): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The selector is invalid');

        /** @phpstan-ignore-next-line */
        new BannedUserAgentHeaderFilter($invalidSelector);
    }
}
