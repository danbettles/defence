<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Factory;

use PHPUnit\Framework\TestCase;
use ThreeStreams\Defence\Tests\TestsFactory\RequestFactory;
use ThreeStreams\Defence\Envelope;
use ThreeStreams\Defence\Factory\FilterFactory;
use ThreeStreams\Defence\Filter\InvalidParameterFilter;
use ThreeStreams\Defence\Logger\NullLogger;

class Createinvalidiso8601dateparameterfilterTest extends TestCase
{
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
     */
    public function testFactoryMethodCreatesAnInvalidparameterfilter($selector, $validator)
    {
        $filter = (new FilterFactory())->createInvalidIso8601DateParameterFilter($selector);

        $this->assertInstanceOf(InvalidParameterFilter::class, $filter);
        $this->assertSame($selector, $filter->getSelector());
        $this->assertSame($validator, $filter->getValidator());
    }

    public function providesRequestsContainingAnInvalidParameter(): array
    {
        $requestFactory = new RequestFactory();

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
     */
    public function testInvokeReturnsTrueIfTheValueOfAParameterIsInvalid(
        $requestIsSuspicious,
        $selector,
        $request
    ) {
        $filter = (new FilterFactory())->createInvalidIso8601DateParameterFilter($selector);
        $envelope = new Envelope($request, new NullLogger);

        $this->assertSame($requestIsSuspicious, $filter($envelope));
    }
}
