<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Factory;

use PHPUnit\Framework\TestCase;
use ThreeStreams\Defence\Tests\TestsFactory\RequestFactory;
use ThreeStreams\Defence\Envelope;
use ThreeStreams\Defence\Factory\FilterFactory;
use ThreeStreams\Defence\Filter\InvalidParameterFilter;
use ThreeStreams\Defence\Logger\NullLogger;

class CreateinvalidmachinedateparameterfilterTest extends TestCase
{
    public function providesFactoryMethodArgs(): array
    {
        return [[
            'selector' => ['starts_on', 'ends_on'],
            'validator' => '/^[\d\-]*$/',
        ], [
            'selector' => '/_on$/',
            'validator' => '/^[\d\-]*$/',
        ]];
    }

    /**
     * @dataProvider providesFactoryMethodArgs
     */
    public function testFactoryMethodCreatesAnInvalidparameterfilter($selector, $validator)
    {
        $filter = (new FilterFactory())->createInvalidMachineDateParameterFilter($selector);

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
            $requestFactory->createGet(['starts_on' => '12-03-2020']),
        ], [
            false,
            ['starts_on'],
            $requestFactory->createGet(['starts_on' => '00-00-0000']),
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
            $requestFactory->createGet(['starts_on' => ' 2020-03-12 ']),
        ], [
            true,
            ['starts_on'],
            $requestFactory->createGet(['starts_on' => ' 12-03-2020 ']),
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
        $filter = (new FilterFactory())->createInvalidMachineDateParameterFilter($selector);
        $envelope = new Envelope($request, new NullLogger);

        $this->assertSame($requestIsSuspicious, $filter($envelope));
    }
}
