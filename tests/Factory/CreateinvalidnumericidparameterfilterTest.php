<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Factory;

use Psr\Log\LogLevel;
use DanBettles\Defence\Envelope;
use DanBettles\Defence\Factory\FilterFactory;
use DanBettles\Defence\Filter\InvalidParameterFilter;
use DanBettles\Defence\Logger\NullLogger;
use DanBettles\Defence\Tests\AbstractTestCase;
use Symfony\Component\HttpFoundation\Request;

use const false;
use const null;
use const true;

/**
 * @phpstan-import-type Selector from InvalidParameterFilter
 */
class CreateinvalidnumericidparameterfilterTest extends AbstractTestCase
{
    /** @return array<mixed[]> */
    public function providesFactoryMethodArgs(): array
    {
        return [[
            'selector' => ['blog_id', 'post_id'],
            'validator' => '/^\d*$/',
        ], [
            'selector' => '/_id$/',
            'validator' => '/^\d*$/',
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
        $filter = (new FilterFactory())->createInvalidNumericIdParameterFilter($selector);

        $this->assertInstanceOf(InvalidParameterFilter::class, $filter);
        $this->assertSame($selector, $filter->getSelector());
        $this->assertSame($validator, $filter->getValidator());
    }

    public function testFactoryMethodAcceptsOptions(): void
    {
        $filter = (new FilterFactory())->createInvalidNumericIdParameterFilter(['id'], [
            'quux' => 'quz',
        ]);

        $this->assertSame([
            'log_level' => LogLevel::WARNING,
            'type' => null,
            'quux' => 'quz',
        ], $filter->getOptions());
    }

    /** @return array<mixed[]> */
    public function providesRequestsContainingAnInvalidParameter(): array
    {
        $requestFactory = $this->getRequestFactory();

        return [[
            false,
            ['post_id'],
            $requestFactory->createGet(['post_id' => '123']),
        ], [
            false,
            ['post_id'],
            $requestFactory->createGet(['post_id' => '0']),
        ], [
            false,
            ['post_id'],
            $requestFactory->createGet(['post_id' => '']),
        ], [
            true,
            ['post_id'],
            $requestFactory->createGet(['post_id' => "71094'A=0"]),
        ], [
            true,
            ['post_id'],
            $requestFactory->createGet(['post_id' => '-123']),
        ], [
            true,
            ['post_id'],
            $requestFactory->createGet(['post_id' => ' ']),
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
        $filter = (new FilterFactory())->createInvalidNumericIdParameterFilter($selector);
        $envelope = new Envelope($request, new NullLogger());

        $this->assertSame($requestIsSuspicious, $filter($envelope));
    }
}
