<?php declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Filter;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ThreeStreams\Defence\Filter\InvalidParameterFilter;
use ThreeStreams\Defence\Filter\AbstractFilter;
use ThreeStreams\Defence\Envelope;
use ThreeStreams\Defence\Tests\TestsFactory\RequestFactory;
use InvalidArgumentException;

class InvalidParameterFilterTest extends TestCase
{
    public function testIsAnAbstractfilter()
    {
        $this->assertTrue(is_subclass_of(InvalidParameterFilter::class, AbstractFilter::class));
    }

    public function providesConstructorArguments(): array
    {
        return [[
            'selector' => ['blog_id', 'post_id'],
            'test' => '/^\d*$/',
        ], [
            'selector' => '/_id$/',
            'test' => '/^\d*$/',
        ]];
    }

    /**
     * @dataProvider providesConstructorArguments
     */
    public function testConstructor($selector, $test)
    {
        $filter = new InvalidParameterFilter($selector, $test);

        $this->assertSame($selector, $filter->getSelector());
        $this->assertSame($test, $filter->getValidator());
    }

    public function testConstructorAcceptsOptions()
    {
        $options = ['foo' => 'bar'];

        $filter = new InvalidParameterFilter([], '//', $options);

        $this->assertSame($options, $filter->getOptions());
    }

    public function providesInvalidSelectors(): array
    {
        return [[
            true,
        ], [
            123,
        ], [
            1.23,
        ], [
            null,
        ]];
    }

    /**
     * @dataProvider providesInvalidSelectors
     */
    public function testConstructorThrowsAnExceptionIfTheSelectorIsInvalid($invalidSelector)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The selector is invalid.');

        new InvalidParameterFilter($invalidSelector, '//');
    }

    public function providesRequestsContainingInvalidParameters(): array
    {
        $requestFactory = new RequestFactory();

        return [[
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createGet([]),
            'selector' => ['id'],  //Specific parameters.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createPost([]),
            'selector' => ['id'],  //Specific parameters.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createGet(['id' => '123']),
            'selector' => ['id'],  //Specific parameters.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createPost(['id' => '123']),
            'selector' => ['id'],  //Specific parameters.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createGet(['id' => '']),
            'selector' => ['id'],  //Specific parameters.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createPost(['id' => '']),
            'selector' => ['id'],  //Specific parameters.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createGet([]),
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createPost([]),
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createGet(['valid_id' => '123']),
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createPost(['valid_id' => '123']),
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createGet(['id' => ['123', '456']]),  //As in `?id[]=123&id[]=456`.
            'selector' => ['id'],  //Specific parameters.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createPost(['id' => ['123', '456']]),  //As in `?id[]=123&id[]=456`.
            'selector' => ['id'],  //Specific parameters.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createGet(['valid_id' => ['123', '456']]),  //As in `?id[]=123&id[]=456`.
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createPost(['valid_id' => ['123', '456']]),  //As in `?id[]=123&id[]=456`.
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createGet(['id' => "71094'A=0"]),
            'selector' => ['id'],  //Specific parameters.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createPost(['id' => "71094'A=0"]),
            'selector' => ['id'],  //Specific parameters.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createGet(['invalid_id' => "71094'A=0"]),
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createPost(['invalid_id' => "71094'A=0"]),
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createGet(['id' => ['123', "71094'A=0"]]),  //As in `?id[]=123&id[]=71094%27A%3D0`.
            'selector' => ['id'],  //Specific parameters.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createPost(['id' => ['123', "71094'A=0"]]),  //As in `?id[]=123&id[]=71094%27A%3D0`.
            'selector' => ['id'],  //Specific parameters.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createGet(['invalid_id' => ['123', "71094'A=0"]]),  //As in `?id[]=123&id[]=71094%27A%3D0`.
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createPost(['invalid_id' => ['123', "71094'A=0"]]),  //As in `?id[]=123&id[]=71094%27A%3D0`.
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createGet(['1564736436' => '']),  //The query string was `?1564736436=`.
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'test' => '/^\d*$/',
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createPost(['1564736436' => '']),  //The query string was `?1564736436=`.
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'test' => '/^\d*$/',
        ]];
    }

    /**
     * @dataProvider providesRequestsContainingInvalidParameters
     */
    public function testInvokeReturnsTrueIfTheValueOfAParameterIsInvalid(
        bool $requestIsSuspicious,
        $request,
        $selector,
        $test
    ) {
        $envelope = new Envelope($request, new NullLogger());
        $filter = new InvalidParameterFilter($selector, $test);

        $this->assertSame($requestIsSuspicious, $filter($envelope));
    }

    public function providesLogMessages(): array
    {
        return [[
            'expectedMessage' => 'The value of `query.foo_id` does not match the regex `/^\d*$/`.',
            'selector' => ['foo_id'],
            'validator' => '/^\d*$/',
            'request' => (new RequestFactory())->createGet(['foo_id' => 'bar']),
        ], [
            'expectedMessage' => 'The value of `request.foo_id` does not match the regex `/^\d*$/`.',
            'selector' => ['foo_id'],
            'validator' => '/^\d*$/',
            'request' => (new RequestFactory())->createPost(['foo_id' => 'bar']),
        ], [
            'expectedMessage' => 'The value of `query.foo_id` does not match the regex `/^\d*$/`.',
            'selector' => '/_id$/',
            'validator' => '/^\d*$/',
            'request' => (new RequestFactory())->createGet(['foo_id' => 'bar']),
        ], [
            'expectedMessage' => 'The value of `request.foo_id` does not match the regex `/^\d*$/`.',
            'selector' => '/_id$/',
            'validator' => '/^\d*$/',
            'request' => (new RequestFactory())->createPost(['foo_id' => 'bar']),
        ], [
            'expectedMessage' => 'The value of `query.bar` does not match the regex `/^[a-z]+$/`.',
            'selector' => ['bar'],
            'validator' => '/^[a-z]+$/',  //A different regex.
            'request' => (new RequestFactory())->createGet(['bar' => 'BAZ']),
        ], [
            'expectedMessage' => 'The value of `request.bar` does not match the regex `/^[a-z]+$/`.',
            'selector' => ['bar'],
            'validator' => '/^[a-z]+$/',  //A different regex.
            'request' => (new RequestFactory())->createPost(['bar' => 'BAZ']),
        ]];
    }

    /**
     * @dataProvider providesLogMessages
     */
    public function testInvokeAddsALogRecordViaTheEnvelope($expectedMessage, $selector, $validator, $request)
    {
        $envelope = $this
            ->getMockBuilder(Envelope::class)
            ->setConstructorArgs([
                $request,
                new NullLogger(),
            ])
            ->setMethods(['addLog'])
            ->getMock()
        ;

        $envelope
            ->expects($this->once())
            ->method('addLog')
            ->with($this->equalTo($expectedMessage))
        ;

        $filter = new InvalidParameterFilter($selector, $validator);

        $this->assertTrue($filter($envelope));
    }

    public function providesTrustfulRequests(): array
    {
        return [[
            'selector' => ['foo_id'],
            'validator' => '/^\d*$/',
            'request' => (new RequestFactory())->createGet(['foo_id' => '123']),
        ], [
            'selector' => ['foo_id'],
            'validator' => '/^\d*$/',
            'request' => (new RequestFactory())->createPost(['foo_id' => '123']),
        ], [
            'selector' => '/_id$/',
            'validator' => '/^\d*$/',
            'request' => (new RequestFactory())->createGet(['foo_id' => '123']),
        ], [
            'selector' => '/_id$/',
            'validator' => '/^\d*$/',
            'request' => (new RequestFactory())->createPost(['foo_id' => '123']),
        ]];
    }

    /**
     * @dataProvider providesTrustfulRequests
     */
    public function testInvokeDoesNotAddALogRecordIfTheRequestIsNotSuspicious($selector, $validator, $request)
    {
        $envelope = $this
            ->getMockBuilder(Envelope::class)
            ->setConstructorArgs([
                $request,
                new NullLogger(),
            ])
            ->setMethods(['addLog'])
            ->getMock()
        ;

        $envelope
            ->expects($this->never())
            ->method('addLog')
        ;

        $filter = new InvalidParameterFilter($selector, $validator);

        $this->assertFalse($filter($envelope));
    }
}
