<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Factory;

use Closure;
use DanBettles\Defence\Envelope;
use DanBettles\Defence\Factory\FilterFactory;
use DanBettles\Defence\Filter\InvalidHeaderFilter;
use DanBettles\Defence\Logger\NullLogger;
use DanBettles\Defence\Tests\AbstractTestCase;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;

use const false;
use const true;

class CreatesuspicioususeragentheaderfilterTest extends AbstractTestCase
{
    public function testFactoryMethodCreatesAnInvalidheaderfilter(): void
    {
        $filter = (new FilterFactory())->createSuspiciousUserAgentHeaderFilter();

        $this->assertInstanceOf(InvalidHeaderFilter::class, $filter);
        $this->assertSame('User-Agent', $filter->getSelector());
        $this->assertInstanceOf(Closure::class, $filter->getValidator());
    }

    public function testFactoryMethodAcceptsOptions(): void
    {
        $filter = (new FilterFactory())->createSuspiciousUserAgentHeaderFilter([
            'foo' => 'bar',
        ]);

        $this->assertSame([
            'log_level' => LogLevel::WARNING,
            'foo' => 'bar',
        ], $filter->getOptions());
    }

    /** @return array<mixed[]> */
    public function providesRequestsWithASuspiciousUserAgentHeader(): array
    {
        $requestFactory = $this->getRequestFactory();

        return [
            // OK
            [
                false,
                $requestFactory->createWithHeaders(['User-Agent' => 'Something']),
            ],
            // [NEW] OK (at least one alpha)
            [
                false,
                $requestFactory->createWithHeaders(['User-Agent' => 'a']),
            ],
            // [NEW] OK (at least one alpha)
            [
                false,
                $requestFactory->createWithHeaders(['User-Agent' => 'a1']),
            ],

            // Suspicious (blank)
            [
                true,
                $requestFactory->createWithHeaders(['User-Agent' => '']),
            ],
            // Suspicious (non-existent)
            [
                true,
                $requestFactory->createWithHeaders([]),
            ],
            // Suspicious (only whitespace)
            [
                true,
                $requestFactory->createWithHeaders(['User-Agent' => ' ']),
            ],
            // Suspicious (only whitespace)
            [
                true,
                $requestFactory->createWithHeaders(['User-Agent' => '   ']),
            ],
            // Suspicious (dash)
            [
                true,
                $requestFactory->createWithHeaders(['User-Agent' => '-']),
            ],
            // Suspicious (dash)
            [
                true,
                $requestFactory->createWithHeaders(['User-Agent' => ' - ']),
            ],

            // [NEW] Suspicious (just numbers)
            [
                true,
                $requestFactory->createWithHeaders(['User-Agent' => '1']),
            ],
            // [NEW] Suspicious (no alpha)
            [
                true,
                $requestFactory->createWithHeaders(['User-Agent' => '!@£123']),
            ],
        ];
    }

    /** @dataProvider providesRequestsWithASuspiciousUserAgentHeader */
    public function testInvokeReturnsTrueIfTheUserAgentHeaderIsSuspicious(
        bool $requestIsSuspicious,
        Request $request
    ): void {
        $envelope = new Envelope($request, new NullLogger());
        $filter = (new FilterFactory())->createSuspiciousUserAgentHeaderFilter();

        $this->assertSame($requestIsSuspicious, $filter($envelope));
    }
}
