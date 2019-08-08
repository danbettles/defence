<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Filter;

use PHPUnit\Framework\TestCase;
use ThreeStreams\Defence\Filter\InvalidMachineDateParameterFilter;
use ThreeStreams\Defence\Filter\AbstractInvalidParameterFilter;
use ThreeStreams\Defence\Logger\NullLogger;
use ThreeStreams\Defence\Envelope;
use ThreeStreams\Defence\Tests\TestsFactory\RequestFactory;

class InvalidMachineDateParameterFilterTest extends TestCase
{
    public function testExtendsAbstractinvalidparameterfilter()
    {
        $this->assertTrue(
            is_subclass_of(InvalidMachineDateParameterFilter::class, AbstractInvalidParameterFilter::class)
        );
    }

    public function testConstructor()
    {
        $parameterNames = [
            'starts_on',
            'ends_on',
        ];

        $allowBlankFilter = new InvalidMachineDateParameterFilter($parameterNames);

        $this->assertSame($parameterNames, $allowBlankFilter->getParameterNames());
        $this->assertTrue($allowBlankFilter->getAllowBlank());

        $doNotAllowBlankFilter = new InvalidMachineDateParameterFilter($parameterNames, false);

        $this->assertSame($parameterNames, $doNotAllowBlankFilter->getParameterNames());
        $this->assertFalse($doNotAllowBlankFilter->getAllowBlank());
    }

    public function providesRequestsWithAnInvalidMachineDateParameter(): array
    {
        $requestFactory = new RequestFactory();

        return [[
            true,
            $requestFactory->createWithGetParameters(['starts_on' => "' AND 1 = 1"]),
            ['ends_on', 'starts_on'],
            'allowBlank' => true,
        ], [
            false,
            $requestFactory->createWithGetParameters(['starts_on' => '2019-07-12']),
            ['ends_on', 'starts_on'],
            'allowBlank' => true,
        ], [
            false,
            $requestFactory->createWithGetParameters(['starts_on' => '12-07-2019']),
            ['ends_on', 'starts_on'],
            'allowBlank' => true,
        ], [
            false,
            $requestFactory->createWithGetParameters(['starts_on' => '']),
            ['ends_on', 'starts_on'],
            'allowBlank' => true,
        ], [
            true,
            $requestFactory->createWithGetParameters(['starts_on' => '']),
            ['ends_on', 'starts_on'],
            'allowBlank' => false,
        ], [
            false,  //Request looks okay, given the config.
            $requestFactory->createWithGetParameters(['created_on' => "' AND 1 = 1"]),
            ['ends_on', 'starts_on'],
            'allowBlank' => true,
        ], [
            true,
            $requestFactory->createWithGetParameters([
                'created_on' => "' AND 1 = 1",
                'ends_on' => "' AND 1 = 1",
            ]),
            ['ends_on', 'starts_on'],
            'allowBlank' => true,
        ], [
            true,
            $requestFactory->createWithGetParameters(['starts_on' => ' ']),
            ['ends_on', 'starts_on'],
            'allowBlank' => true,
        ], [
            true,
            //We're strict: our code shouldn't be sloppy like this, and we don't want it to be sloppy, either.
            $requestFactory->createWithGetParameters(['starts_on' => ' 2019-07-12 ']),
            ['ends_on', 'starts_on'],
            'allowBlank' => true,
        ], [
            true,
            //We're strict: our code shouldn't be sloppy like this, and we don't want it to be sloppy, either.
            $requestFactory->createWithGetParameters(['starts_on' => ' 12-07-2019 ']),
            ['ends_on', 'starts_on'],
            'allowBlank' => true,
        ], [
            true,
            $requestFactory->createWithGetParameters(['search_dates' => [
                '2019-07-12',
                '12-07-2019',
                'foo',
            ]]),  //An array of dates.
            ['search_dates'],
            'allowBlank' => true,
        ], [
            true,
            $requestFactory->createWithGetParameters(['search_dates' => [
                '2019-07-12',
                '12-07-2019',
                '',
            ]]),
            ['search_dates'],
            'allowBlank' => false,
        ], [
            false,
            $requestFactory->createWithGetParameters(['search_dates' => [
                '2019-07-12',
                '12-07-2019',
                '',
            ]]),
            ['search_dates'],
            'allowBlank' => true,
        ]];
    }

    /**
     * @dataProvider providesRequestsWithAnInvalidMachineDateParameter
     */
    public function testInvokeReturnsTrueIfTheValueOfAMachineDateParameterIsInvalid(
        $expected,
        $request,
        $parameterNames,
        $allowBlank
    ) {
        $logger = new NullLogger();
        $envelope = new Envelope($request, $logger);

        $filter = new InvalidMachineDateParameterFilter($parameterNames, $allowBlank);

        $this->assertSame($expected, $filter($envelope));
    }

    public function testInvokeAddsALogToTheEnvelope()
    {
        $request = (new RequestFactory())->createWithGetParameters(['starts_on' => 'foo']);
        $logger = new NullLogger();

        $envelope = $this
            ->getMockBuilder(Envelope::class)
            ->setConstructorArgs([$request, $logger])
            ->setMethods(['addLog'])
            ->getMock()
        ;

        $envelope
            ->expects($this->once())
            ->method('addLog')
            ->with($this->equalTo('The value of `query.starts_on` does not match the regex `/^[\d\-]+$/`.'))
        ;

        $filter = new InvalidMachineDateParameterFilter(['starts_on']);
        $requestSuspicious = $filter($envelope);

        $this->assertTrue($requestSuspicious);
    }
}
