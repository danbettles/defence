<?php declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Factory;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use ThreeStreams\Defence\Tests\TestsFactory\RequestFactory;
use ThreeStreams\Defence\Envelope;
use ThreeStreams\Defence\Factory\FilterFactory;
use ThreeStreams\Defence\Filter\InvalidParameterFilter;
use ThreeStreams\Defence\Logger\NullLogger;

class CreateinvalidnumericidparameterfilterTest extends TestCase
{
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
     */
    public function testFactoryMethodCreatesAnInvalidparameterfilter($selector, $validator)
    {
        $filter = (new FilterFactory())->createInvalidNumericIdParameterFilter($selector);

        $this->assertInstanceOf(InvalidParameterFilter::class, $filter);
        $this->assertSame($selector, $filter->getSelector());
        $this->assertSame($validator, $filter->getValidator());
    }

    public function testFactoryMethodAcceptsOptions()
    {
        $filter = (new FilterFactory())->createInvalidNumericIdParameterFilter(['id'], [
            'quux' => 'quz',
        ]);

        $this->assertSame([
            'log_level' => LogLevel::WARNING,
            'quux' => 'quz',
        ], $filter->getOptions());
    }

    public function providesRequestsContainingAnInvalidParameter(): array
    {
        $requestFactory = new RequestFactory();

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
     */
    public function testInvokeReturnsTrueIfTheValueOfAParameterIsInvalid(
        $requestIsSuspicious,
        $selector,
        $request
    ) {
        $filter = (new FilterFactory())->createInvalidNumericIdParameterFilter($selector);
        $envelope = new Envelope($request, new NullLogger);

        $this->assertSame($requestIsSuspicious, $filter($envelope));
    }
}
