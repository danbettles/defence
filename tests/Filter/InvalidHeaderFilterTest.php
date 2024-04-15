<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Filter;

use DanBettles\Defence\Envelope;
use DanBettles\Defence\Filter\AbstractFilter;
use DanBettles\Defence\Filter\InvalidHeaderFilter;
use DanBettles\Defence\Tests\AbstractTestCase;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;

use const false;
use const true;

/**
 * @phpstan-type FilterArgs array{selector:string,validator:\Closure}
 */
class InvalidHeaderFilterTest extends AbstractTestCase
{
    public function testIsAFilter(): void
    {
        $this->assertSubclassOf(AbstractFilter::class, InvalidHeaderFilter::class);
    }

    public function testConstructor(): void
    {
        $selector = 'User-Agent';
        $validator = fn () => true;

        $filter = new InvalidHeaderFilter($selector, $validator);

        $this->assertSame($selector, $filter->getSelector());
        $this->assertSame($validator, $filter->getValidator());

        $this->assertSame([
            'log_level' => LogLevel::WARNING,
        ], $filter->getOptions());
    }

    public function testConstructorAcceptsOptions(): void
    {
        $filter = new InvalidHeaderFilter('Anything', fn () => true, [
            'foo' => 'bar',
        ]);

        $this->assertSame([
            'log_level' => LogLevel::WARNING,
            'foo' => 'bar',
        ], $filter->getOptions());
    }

    /** @return array<mixed[]> */
    public function providesInvalidRequests(): array
    {
        $request = $this
            ->getRequestFactory()
            ->createWithHeaders(['User-Agent' => 'anything'])
        ;

        $validator = fn (): bool => false;

        return [
            // Title-case selector
            [
                'expectedLogMessage' => 'The value of header `User-Agent`, `anything`, is invalid',
                $request,
                ['selector' => 'User-Agent', 'validator' => $validator],
            ],
            // Lowercase selector
            [
                'expectedLogMessage' => 'The value of header `user-agent`, `anything`, is invalid',
                $request,
                ['selector' => 'user-agent', 'validator' => $validator],
            ],
        ];
    }

    /**
     * @dataProvider providesInvalidRequests
     * @phpstan-param FilterArgs $filterArgs
     */
    public function testInvokeReturnsTrueIfTheValueOfAHeaderIsInvalid(
        ?string $expectedLogMessage,
        Request $request,
        array $filterArgs,
    ): void {
        $envelope = new Envelope($request, new NullLogger());

        $mockFilter = $this
            ->getMockBuilder(InvalidHeaderFilter::class)
            ->setConstructorArgs($filterArgs)
            ->onlyMethods(['envelopeAddLogEntry'])
            ->getMock()
        ;

        $mockFilter
            ->expects($this->once())
            ->method('envelopeAddLogEntry')
            ->with($envelope, $expectedLogMessage)
        ;

        $this->assertTrue($mockFilter($envelope));
    }

    /** @return array<mixed[]> */
    public function providesValidRequests(): array
    {
        $request = $this
            ->getRequestFactory()
            ->createWithHeaders(['User-Agent' => 'anything'])
        ;

        $validator = fn (): bool => true;

        return [
            // Title-case selector
            [
                $request,
                'filterArgs' => ['selector' => 'User-Agent', 'validator' => $validator],
            ],
            // Lowercase selector
            [
                $request,
                'filterArgs' => ['selector' => 'user-agent', 'validator' => $validator],
            ],
        ];
    }

    /**
     * @dataProvider providesValidRequests
     * @phpstan-param FilterArgs $filterArgs
     */
    public function testInvokeReturnsFalseIfTheValueOfAHeaderIsValid(
        Request $request,
        array $filterArgs,
    ): void {
        $mockFilter = $this
            ->getMockBuilder(InvalidHeaderFilter::class)
            ->setConstructorArgs($filterArgs)
            ->onlyMethods(['envelopeAddLogEntry'])
            ->getMock()
        ;

        $mockFilter
            ->expects($this->never())
            ->method('envelopeAddLogEntry')
        ;

        $envelope = new Envelope($request, new NullLogger());

        $this->assertFalse($mockFilter($envelope));
    }
}
