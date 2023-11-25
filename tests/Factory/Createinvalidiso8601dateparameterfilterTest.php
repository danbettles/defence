<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Factory;

use DanBettles\Defence\Envelope;
use DanBettles\Defence\Factory\FilterFactory;
use DanBettles\Defence\Filter\InvalidParameterFilter;
use DanBettles\Defence\Logger\NullLogger;
use DanBettles\Defence\Tests\AbstractTestCase;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;

use const false;
use const true;

/**
 * @phpstan-import-type Selector from InvalidParameterFilter
 */
class Createinvalidiso8601dateparameterfilterTest extends AbstractTestCase
{
    /** @return array<mixed[]> */
    public function providesFactoryMethodArgs(): array
    {
        return [[
            'selector' => ['starts_on', 'ends_on'],
            'validator' => '/^(\d{4}-\d{2}-\d{2}|)$/',
        ], [
            'selector' => '/_on$/',
            'validator' => '/^(\d{4}-\d{2}-\d{2}|)$/',
        ]];
    }

    /**
     * @dataProvider providesFactoryMethodArgs
     * @phpstan-param Selector $selector
     */
    public function testFactoryMethodCreatesAnInvalidparameterfilter(
        $selector,
        string $validator
    ): void {
        $filter = (new FilterFactory())->createInvalidIso8601DateParameterFilter($selector);

        $this->assertInstanceOf(InvalidParameterFilter::class, $filter);
        $this->assertSame($selector, $filter->getSelector());
        $this->assertSame($validator, $filter->getValidator());
    }

    public function testFactoryMethodAcceptsOptions(): void
    {
        $filter = (new FilterFactory())->createInvalidIso8601DateParameterFilter(['starts_on'], [
            'foo' => 'bar',
        ]);

        $this->assertSame([
            'log_level' => LogLevel::WARNING,
            'foo' => 'bar',
        ], $filter->getOptions());
    }

    /** @return array<mixed[]> */
    public function providesRequestsContainingAnInvalidParameter(): array
    {
        $requestFactory = $this->getRequestFactory();

        return [[
            false,
            ['starts_on'],
            $requestFactory->createGet(['starts_on' => '2020-03-12']),
        ], [
            false,
            ['starts_on'],
            $requestFactory->createGet(['starts_on' => '0000-00-00']),
        ], [
            false,
            ['starts_on'],
            $requestFactory->createGet(['starts_on' => '']),
        ], [
            true,
            ['starts_on'],
            $requestFactory->createGet(['starts_on' => "71094'A=0"]),
        ], [
            true,
            ['starts_on'],
            $requestFactory->createGet(['starts_on' => "'"]),
        ], [
            true,
            ['starts_on'],
            $requestFactory->createGet(['starts_on' => '12-03-2020']),
        ], [
            true,
            ['starts_on'],
            $requestFactory->createGet(['starts_on' => ' 2020-03-12 ']),
        ], [
            true,
            ['starts_on'],
            $requestFactory->createGet(['starts_on' => ' ']),
        ]];
    }

    /**
     * @dataProvider providesRequestsContainingAnInvalidParameter
     * @phpstan-param Selector $selector
     */
    public function testInvokeReturnsTrueIfTheValueOfAParameterIsInvalid(
        bool $requestIsSuspicious,
        $selector,
        Request $request
    ): void {
        $filter = (new FilterFactory())->createInvalidIso8601DateParameterFilter($selector);
        $envelope = new Envelope($request, new NullLogger());

        $this->assertSame($requestIsSuspicious, $filter($envelope));
    }
}
