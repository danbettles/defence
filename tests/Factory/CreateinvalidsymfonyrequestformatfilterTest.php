<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Factory;

use DanBettles\Defence\Envelope;
use DanBettles\Defence\Factory\FilterFactory;
use DanBettles\Defence\Filter\InvalidParameterFilter;
use DanBettles\Defence\Logger\NullLogger;
use DanBettles\Defence\Tests\AbstractTestCase;
use DanBettles\Defence\Tests\TestsFactory\RequestFactory;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;

/**
 * @phpstan-import-type IncomingFilterOptions from \DanBettles\Defence\Filter\AbstractFilter
 * @phpstan-import-type AugmentedFilterOptions from InvalidParameterFilter
 * @phpstan-import-type Selector from InvalidParameterFilter
 */
class CreateinvalidsymfonyrequestformatfilterTest extends AbstractTestCase
{
    /** @return array<mixed[]> */
    public function providesFactoryMethodArgs(): array
    {
        return [
            [
                [
                    '_format',
                ],
                '~^[a-zA-Z0-9\-]+$~',
                [
                    'log_level' => LogLevel::WARNING,
                    'type' => 'string',
                ],
                [],
            ],
            [
                [
                    '_request_format',
                ],
                '~^[a-zA-Z0-9\-]+$~',
                [
                    'log_level' => LogLevel::WARNING,
                    'type' => 'string',
                ],
                [
                    'parameter_name' => '_request_format',
                ],
            ],
        ];
    }

    /**
     * @dataProvider providesFactoryMethodArgs
     * @phpstan-param string[] $expectedSelector
     * @phpstan-param AugmentedFilterOptions $expectedOptions
     * @phpstan-param IncomingFilterOptions $options
     */
    public function testFactoryMethodCreatesAnInvalidparameterfilter(
        array $expectedSelector,
        string $expectedValidator,
        array $expectedOptions,
        array $options
    ): void {
        $filter = (new FilterFactory())->createInvalidSymfonyRequestFormatFilter($options);

        $this->assertInstanceOf(InvalidParameterFilter::class, $filter);
        $this->assertSame($expectedSelector, $filter->getSelector());
        $this->assertSame($expectedValidator, $filter->getValidator());
        $this->assertSame($expectedOptions, $filter->getOptions());
    }

    /** @return array<mixed[]> */
    public function providesRequestFormats(): array
    {
        $requestFactory = new RequestFactory();

        $somethings = [
            [false, '_format', ['_format' => 'json']],
            [false, '_format', ['_format' => 'a1']],
            [false, '_format', ['_format' => '1a']],
            [false, '_format', ['_format' => 'a-1']],
            [false, '_format', ['_format' => '1-a']],
            [false, '_request_format', ['_request_format' => 'json']],
            [false, '_request_format', ['_request_format' => 'a1']],
            [false, '_request_format', ['_request_format' => '1a']],
            [false, '_request_format', ['_request_format' => 'a-1']],
            [false, '_request_format', ['_request_format' => '1-a']],
            [false, '_format', ['_request_format' => '#new Foo(); // Any old junk']],
            [false, '_format', ['_request_format' => []]],

            [true, '_format', ['_format' => '#new Foo(); // Any old junk']],
            [true, '_format', ['_format' => []]],
            [true, '_format', ['_format' => '']],
            [true, '_request_format', ['_request_format' => '#new Foo(); // Any old junk']],
            [true, '_request_format', ['_request_format' => []]],
            [true, '_request_format', ['_request_format' => '']],
        ];

        $argLists = [];

        foreach ($somethings as $something) {
            $requestIsSuspicious = $something[0];
            $parameterName = $something[1];
            $parameters = $something[2];

            $argLists[] = [
                $requestIsSuspicious,
                $requestFactory->createGet($parameters),
                $parameterName,
            ];

            $argLists[] = [
                $requestIsSuspicious,
                $requestFactory->createPost($parameters),
                $parameterName,
            ];
        }

        return $argLists;
    }

    /** @dataProvider providesRequestFormats */
    public function testInvokeReturnsTrueIfTheValueOfTheRequestFormatIsInvalid(
        bool $requestIsSuspicious,
        Request $request,
        string $parameterName
    ): void {
        $envelope = new Envelope($request, new NullLogger());

        $filter = (new FilterFactory())->createInvalidSymfonyRequestFormatFilter([
            'parameter_name' => $parameterName,
        ]);

        $this->assertSame($requestIsSuspicious, $filter($envelope));
    }
}
