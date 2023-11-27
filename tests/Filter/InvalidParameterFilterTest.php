<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Filter;

use Psr\Log\NullLogger;
use Psr\Log\LogLevel;
use DanBettles\Defence\Filter\InvalidParameterFilter;
use DanBettles\Defence\Filter\AbstractFilter;
use DanBettles\Defence\Envelope;
use DanBettles\Defence\Tests\AbstractTestCase;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

use function strtoupper;

use const false;
use const null;
use const true;

/**
 * @phpstan-import-type Selector from InvalidParameterFilter
 * @phpstan-import-type AugmentedFilterOptions from InvalidParameterFilter
 */
class InvalidParameterFilterTest extends AbstractTestCase
{
    public function testIsAFilter(): void
    {
        $this->assertSubclassOf(AbstractFilter::class, InvalidParameterFilter::class);
    }

    /** @return array<mixed[]> */
    public function providesConstructorArguments(): array
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
     * @dataProvider providesConstructorArguments
     * @phpstan-param Selector $selector
     */
    public function testConstructor(
        $selector,
        string $validator
    ): void {
        $filter = new InvalidParameterFilter($selector, $validator);

        $this->assertSame($selector, $filter->getSelector());
        $this->assertSame($validator, $filter->getValidator());

        $this->assertSame([
            'log_level' => LogLevel::WARNING,
            'type' => null,
        ], $filter->getOptions());
    }

    public function testConstructorAcceptsOptions(): void
    {
        $filter = new InvalidParameterFilter([], '//', [
            'foo' => 'bar',
        ]);

        $this->assertSame([
            'log_level' => LogLevel::WARNING,
            'type' => null,
            'foo' => 'bar',
        ], $filter->getOptions());
    }

    /** @return array<mixed[]> */
    public function providesInvalidTypeNames(): array
    {
        $invalidTypeNames = [
            'bool',
            'boolean',
            'int',
            'integer',
            'float',
            'double',
            'object',
            'resource',
            'resource (closed)',
            'null',
            'unknown type',
        ];

        $argLists = [];

        foreach ($invalidTypeNames as $invalidTypeName) {
            $argLists[] = [
                $invalidTypeName,
            ];

            $argLists[] = [
                strtoupper($invalidTypeName),
            ];
        }

        return $argLists;
    }

    /** @dataProvider providesInvalidTypeNames */
    public function testConstructorThrowsAnExceptionIfTheValueOfTheTypeOptionIsInvalid(string $invalidTypeName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option `type` is not one of: `NULL`, `"string"`, `"array"`');

        new InvalidParameterFilter(['ignored'], 'ignored', [
            'type' => $invalidTypeName,
        ]);
    }

    /** @return array<mixed[]> */
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
     * @param mixed $invalidSelector
     */
    public function testConstructorThrowsAnExceptionIfTheSelectorIsInvalid($invalidSelector): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The selector is invalid.');

        /** @phpstan-ignore-next-line */
        new InvalidParameterFilter($invalidSelector, '//');
    }

    /** @return array<mixed[]> */
    public function providesRequestsContainingInvalidParameters(): array
    {
        $requestFactory = $this->getRequestFactory();

        $argLists = [[
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createGet([]),
            'selector' => ['id'],  //Specific parameters.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createPost([]),
            'selector' => ['id'],  //Specific parameters.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createGet(['id' => '123']),
            'selector' => ['id'],  //Specific parameters.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createPost(['id' => '123']),
            'selector' => ['id'],  //Specific parameters.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createGet(['id' => '']),
            'selector' => ['id'],  //Specific parameters.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createPost(['id' => '']),
            'selector' => ['id'],  //Specific parameters.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createGet([]),
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createPost([]),
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createGet(['valid_id' => '123']),
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createPost(['valid_id' => '123']),
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createGet(['id' => ['123', '456']]),  // As in `?id[]=123&id[]=456`
            'selector' => ['id'],  //Specific parameters.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createPost(['id' => ['123', '456']]),  // As in `?id[]=123&id[]=456`.
            'selector' => ['id'],  //Specific parameters.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createGet(['valid_id' => ['123', '456']]),  // As in `?id[]=123&id[]=456`.
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => false,
            'request' => $requestFactory->createPost(['valid_id' => ['123', '456']]),  // As in `?id[]=123&id[]=456`.
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createGet(['id' => "71094'A=0"]),
            'selector' => ['id'],  //Specific parameters.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createPost(['id' => "71094'A=0"]),
            'selector' => ['id'],  //Specific parameters.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createGet(['invalid_id' => "71094'A=0"]),
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createPost(['invalid_id' => "71094'A=0"]),
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createGet(['id' => ['123', "71094'A=0"]]),  // As in `?id[]=123&id[]=71094%27A%3D0`.
            'selector' => ['id'],  //Specific parameters.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createPost(['id' => ['123', "71094'A=0"]]),  // As in `?id[]=123&id[]=71094%27A%3D0`.
            'selector' => ['id'],  //Specific parameters.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createGet(['invalid_id' => ['123', "71094'A=0"]]),  // As in `?id[]=123&id[]=71094%27A%3D0`.
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createPost(['invalid_id' => ['123', "71094'A=0"]]),  // As in `?id[]=123&id[]=71094%27A%3D0`.
            'selector' => '/_id$/',  //All parameters with names matching a regex.
            'validator' => '/^\d*$/',
            'options' => [],
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createGet(['array_of_valid_ids' => ['123']]),  // As in `?array_of_valid_ids[]=123`.
            'selector' => ['array_of_valid_ids'],
            'validator' => '/^\d*$/',
            'options' => ['type' => 'string'],
        ], [
            'requestIsSuspicious' => true,
            'request' => $requestFactory->createPost(['array_of_valid_ids' => ['123']]),  // As in `?array_of_valid_ids[]=123`.
            'selector' => ['array_of_valid_ids'],
            'validator' => '/^\d*$/',
            'options' => ['type' => 'string'],
        ]];

        $getRequest = $requestFactory->createGet();
        $getRequest->query->set('1564736436', '');  // The query string was `?1564736436=`

        $argLists[] = [
            'requestIsSuspicious' => false,
            'request' => $getRequest,
            'selector' => '/_id$/',  // All parameters with names matching a regex
            'validator' => '/^\d*$/',
            'options' => [],
        ];

        $postRequest = $requestFactory->createPost();
        $postRequest->request->set('1564736436', '');  // The query string was `?1564736436=`

        $argLists[] = [
            'requestIsSuspicious' => false,
            'request' => $postRequest,
            'selector' => '/_id$/',  // All parameters with names matching a regex
            'validator' => '/^\d*$/',
            'options' => [],
        ];

        return $argLists;
    }

    /**
     * @dataProvider providesRequestsContainingInvalidParameters
     * @phpstan-param Selector $selector
     * @phpstan-param AugmentedFilterOptions $options
     */
    public function testInvokeReturnsTrueIfTheValueOfAParameterIsInvalid(
        bool $requestIsSuspicious,
        Request $request,
        $selector,
        string $validator,
        array $options
    ): void {
        $envelope = new Envelope($request, new NullLogger());
        $filter = new InvalidParameterFilter($selector, $validator, $options);

        $this->assertSame($requestIsSuspicious, $filter($envelope));
    }

    /** @return array<mixed[]> */
    public function providesLogMessages(): array
    {
        $requestFactory = $this->getRequestFactory();

        return [
            [
                'expectedMessage' => 'The value of `query.foo_id` failed validation using the regex `/^\d*$/`.',
                'selector' => ['foo_id'],
                'validator' => '/^\d*$/',
                'options' => [],
                'request' => $requestFactory->createGet(['foo_id' => 'bar']),
            ],
            [
                'expectedMessage' => 'The value of `request.foo_id` failed validation using the regex `/^\d*$/`.',
                'selector' => ['foo_id'],
                'validator' => '/^\d*$/',
                'options' => [],
                'request' => $requestFactory->createPost(['foo_id' => 'bar']),
            ],
            [
                'expectedMessage' => 'The value of `query.foo_id` failed validation using the regex `/^\d*$/`.',
                'selector' => '/_id$/',
                'validator' => '/^\d*$/',
                'options' => [],
                'request' => $requestFactory->createGet(['foo_id' => 'bar']),
            ],
            [
                'expectedMessage' => 'The value of `request.foo_id` failed validation using the regex `/^\d*$/`.',
                'selector' => '/_id$/',
                'validator' => '/^\d*$/',
                'options' => [],
                'request' => $requestFactory->createPost(['foo_id' => 'bar']),
            ],
            [
                'expectedMessage' => 'The value of `query.bar` failed validation using the regex `/^[a-z]+$/`.',
                'selector' => ['bar'],
                'validator' => '/^[a-z]+$/',  //A different regex.
                'options' => [],
                'request' => $requestFactory->createGet(['bar' => 'BAZ']),
            ],
            [
                'expectedMessage' => 'The value of `request.bar` failed validation using the regex `/^[a-z]+$/`.',
                'selector' => ['bar'],
                'validator' => '/^[a-z]+$/',  //A different regex.
                'options' => [],
                'request' => $requestFactory->createPost(['bar' => 'BAZ']),
            ],
            [
                'expectedMessage' => 'The value of `query.foo` is not of type `string`',
                'selector' => ['foo'],
                'validator' => 'ignored',
                'options' => ['type' => 'string'],
                'request' => $requestFactory->createGet(['foo' => ['bar', 'baz']]),
            ],
            [
                'expectedMessage' => 'The value of `request.foo` is not of type `string`',
                'selector' => ['foo'],
                'validator' => 'ignored',
                'options' => ['type' => 'string'],
                'request' => $requestFactory->createPost(['foo' => ['bar', 'baz']]),
            ],
            [
                'expectedMessage' => 'The value of `query.foo` is not of type `array`',
                'selector' => ['foo'],
                'validator' => 'ignored',
                'options' => ['type' => 'array'],
                'request' => $requestFactory->createGet(['foo' => 'bar']),
            ],
            [
                'expectedMessage' => 'The value of `request.foo` is not of type `array`',
                'selector' => ['foo'],
                'validator' => 'ignored',
                'options' => ['type' => 'array'],
                'request' => $requestFactory->createPost(['foo' => 'bar']),
            ],
        ];
    }

    /**
     * @dataProvider providesLogMessages
     * @phpstan-param Selector $selector
     * @phpstan-param AugmentedFilterOptions $options
     */
    public function testInvokeWillAddALogEntryIfTheRequestIsSuspicious(
        string $expectedMessage,
        $selector,
        string $validator,
        array $options,
        Request $request
    ): void {
        $envelope = new Envelope($request, new NullLogger());

        $filterMock = $this
            ->getMockBuilder(InvalidParameterFilter::class)
            ->setConstructorArgs([
                $selector,
                $validator,
                $options,
            ])
            ->onlyMethods(['envelopeAddLogEntry'])
            ->getMock()
        ;

        $filterMock
            ->expects($this->once())
            ->method('envelopeAddLogEntry')
            ->with($envelope, $expectedMessage)
        ;

        $this->assertTrue($filterMock($envelope));
    }

    /** @return array<mixed[]> */
    public function providesTrustfulRequests(): array
    {
        $requestFactory = $this->getRequestFactory();

        return [[
            'selector' => ['foo_id'],
            'validator' => '/^\d*$/',
            'request' => $requestFactory->createGet(['foo_id' => '123']),
        ], [
            'selector' => ['foo_id'],
            'validator' => '/^\d*$/',
            'request' => $requestFactory->createPost(['foo_id' => '123']),
        ], [
            'selector' => '/_id$/',
            'validator' => '/^\d*$/',
            'request' => $requestFactory->createGet(['foo_id' => '123']),
        ], [
            'selector' => '/_id$/',
            'validator' => '/^\d*$/',
            'request' => $requestFactory->createPost(['foo_id' => '123']),
        ]];
    }

    /**
     * @dataProvider providesTrustfulRequests
     * @phpstan-param Selector $selector
     */
    public function testInvokeWillNotAddALogEntryIfTheRequestIsNotSuspicious(
        $selector,
        string $validator,
        Request $request
    ): void {
        $envelope = new Envelope($request, new NullLogger());

        $filterMock = $this
            ->getMockBuilder(InvalidParameterFilter::class)
            ->setConstructorArgs([
                $selector,
                $validator,
            ])
            ->onlyMethods(['envelopeAddLogEntry'])
            ->getMock()
        ;

        $filterMock
            ->expects($this->never())
            ->method('envelopeAddLogEntry')
        ;

        $this->assertFalse($filterMock($envelope));
    }
}
