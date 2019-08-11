<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Filter;

use PHPUnit\Framework\TestCase;
use ThreeStreams\Defence\Filter\InvalidNumericIdParameterFilter;
use ThreeStreams\Defence\Filter\AbstractInvalidParameterFilter;
use ThreeStreams\Defence\Logger\NullLogger;
use ThreeStreams\Defence\Envelope;
use ThreeStreams\Defence\Tests\TestsFactory\RequestFactory;

class InvalidNumericIdParameterFilterTest extends TestCase
{
    public function testExtendsAbstractinvalidparameterfilter()
    {
        $this->assertTrue(
            is_subclass_of(InvalidNumericIdParameterFilter::class, AbstractInvalidParameterFilter::class)
        );
    }

    public function testConstructor()
    {
        $parameterNames = [
            'blog_id',
            'post_id',
        ];

        $allowBlankFilter = new InvalidNumericIdParameterFilter($parameterNames);

        $this->assertSame($parameterNames, $allowBlankFilter->getParameterNames());
        $this->assertTrue($allowBlankFilter->getAllowBlank());

        $doNotAllowBlankFilter = new InvalidNumericIdParameterFilter($parameterNames, false);

        $this->assertSame($parameterNames, $doNotAllowBlankFilter->getParameterNames());
        $this->assertFalse($doNotAllowBlankFilter->getAllowBlank());
    }

    public function providesRequestsWithAnInvalidNumericIdParameter(): array
    {
        $requestFactory = new RequestFactory();

        return [[
            true,
            $requestFactory->createGet(['post_id' => "' AND 1 = 1"]),
            ['blog_id', 'post_id'],
            'allowBlank' => true,  //The default.
        ], [
            false,
            $requestFactory->createGet(['post_id' => '1']),
            ['blog_id', 'post_id'],
            'allowBlank' => true,
        ], [
            false,
            $requestFactory->createGet(['post_id' => '0']),
            ['blog_id', 'post_id'],
            'allowBlank' => true,
        ], [
            true,
            $requestFactory->createGet(['post_id' => '-1']),
            ['blog_id', 'post_id'],
            'allowBlank' => true,
        ], [
            false,  //Request looks okay.
            $requestFactory->createGet(['post_id' => '']),
            ['blog_id', 'post_id'],
            'allowBlank' => true,  //Allow blank, the default.
        ], [
            true,  //We're not expecting blanks in this case, so this request is suspicious.
            $requestFactory->createGet(['post_id' => '']),
            ['blog_id', 'post_id'],
            'allowBlank' => false,
        ], [
            false,  //Request looks okay, given the config.
            $requestFactory->createGet(['foo_id' => "' AND 1 = 1"]),
            ['blog_id', 'post_id'],
            'allowBlank' => true,
        ], [
            true,
            $requestFactory->createGet([
                'foo_id' => "' AND 1 = 1",
                'blog_id' => "' AND 1 = 1",
            ]),
            ['blog_id', 'post_id'],
            'allowBlank' => true,
        ], [
            true,
            $requestFactory->createGet(['searchLocation' => [
                "71094'A=0",
                '123',
            ]]),  //An array of IDs.
            ['searchLocation'],
            'allowBlank' => true,
        ], [
            true,
            $requestFactory->createGet(['searchLocation' => [
                '123',
                '',
            ]]),  //An array of IDs.
            ['searchLocation'],
            'allowBlank' => false,
        ], [
            false,
            $requestFactory->createGet(['searchLocation' => [
                '123',
                '',
            ]]),  //An array of IDs.
            ['searchLocation'],
            'allowBlank' => true,
        ]];
    }

    /**
     * @dataProvider providesRequestsWithAnInvalidNumericIdParameter
     */
    public function testInvokeReturnsTrueIfTheValueOfANumericIdParameterIsInvalid(
        $expected,
        $request,
        $parameterNames,
        $allowBlank
    ) {
        $logger = new NullLogger();
        $envelope = new Envelope($request, $logger);

        $filter = new InvalidNumericIdParameterFilter($parameterNames, $allowBlank);

        $this->assertSame($expected, $filter($envelope));
    }

    public function testInvokeAddsALogRecordViaTheEnvelope()
    {
        $request = (new RequestFactory())->createGet(['foo_id' => 'bar']);
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
            ->with($this->equalTo('The value of `query.foo_id` does not match the regex `/^\d+$/`.'))
        ;

        $filter = new InvalidNumericIdParameterFilter(['foo_id']);
        $requestSuspicious = $filter($envelope);

        $this->assertTrue($requestSuspicious);
    }
}
