<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Filter;

use DanBettles\Defence\Envelope;
use DanBettles\Defence\Filter\AbstractFilter;
use DanBettles\Defence\Filter\InvalidSymfonyHttpMethodOverrideFilter;
use DanBettles\Defence\Logger\NullLogger;
use DanBettles\Defence\Tests\AbstractTestCase;
use Symfony\Component\HttpFoundation\Request;

use function array_filter;
use function strtolower;

use const false;
use const true;

/**
 * Start here: https://github.com/symfony/symfony/blob/4.3/src/Symfony/Component/HttpFoundation/Request.php#L1231
 */
class InvalidSymfonyHttpMethodOverrideFilterTest extends AbstractTestCase
{
    //###> Factory Methods ###
    /**
     * @param mixed $overrideMethod Allow anything so we can try to break other code
     */
    private function createRequestWithMethodOverrideInTheBody(string $realMethod, $overrideMethod): Request
    {
        /** @var string $overrideMethod To keep PHPStan off our backs */
        return $this->getRequestFactory()->create([
            'method' => $realMethod,
            'body' => [
                '_method' => $overrideMethod,
            ],
        ]);
    }

    /**
     * @param mixed $overrideMethod Allow anything so we can try to break other code
     */
    private function createRequestWithMethodOverrideInTheQuery(string $realMethod, $overrideMethod): Request
    {
        /** @var string $overrideMethod To keep PHPStan off our backs */
        return $this->getRequestFactory()->create([
            'method' => $realMethod,
            'query' => [
                '_method' => $overrideMethod,
            ],
        ]);
    }

    private function createRequestWithMethodOverrideInAHeader(string $realMethod, string $overrideMethod): Request
    {
        return $this->getRequestFactory()->create([
            'method' => $realMethod,
            'headers' => [
                'X-Http-Method-Override' => $overrideMethod,
            ],
        ]);
    }
    //###< Factory Methods ###

    public function testIsAFilter(): void
    {
        $this->assertSubclassOf(AbstractFilter::class, InvalidSymfonyHttpMethodOverrideFilter::class);
    }

    /** @return array<mixed[]> */
    public function providesRequests(): array
    {
        $requestFactory = $this->getRequestFactory();

        $invalidOverrides = [
            'foo',
            'FOO',  // Still invalid
            '__construct',  // Something we've seen in the wild
        ];

        $invalidOverrideInArray = ['foo' => 'bar'];

        $argLists = [];

        // Invalid overrides should *always* yield `true` (i.e. "yes, suspicious")
        foreach (InvalidSymfonyHttpMethodOverrideFilter::VALID_METHODS as $validRealMethod) {
            foreach ($invalidOverrides as $invalidOverride) {
                $argLists[] = [true, $this->createRequestWithMethodOverrideInTheBody($validRealMethod, $invalidOverride)];
                $argLists[] = [true, $this->createRequestWithMethodOverrideInTheQuery($validRealMethod, $invalidOverride)];
                $argLists[] = [true, $this->createRequestWithMethodOverrideInAHeader($validRealMethod, $invalidOverride)];
            }

            $argLists[] = [true, $this->createRequestWithMethodOverrideInTheBody($validRealMethod, $invalidOverrideInArray)];
            $argLists[] = [true, $this->createRequestWithMethodOverrideInTheQuery($validRealMethod, $invalidOverrideInArray)];
        }

        $incompatibleRealMethods = array_filter(
            InvalidSymfonyHttpMethodOverrideFilter::VALID_METHODS,
            fn (string $methodName) => Request::METHOD_POST !== $methodName
        );

        // The method can be overridden only if the 'real' method is `POST`, so other types of request containing
        // overrides are suspicious -- irrespective of the value submitted
        foreach ($incompatibleRealMethods as $incompatibleRealMethod) {
            foreach ($invalidOverrides as $invalidOverride) {
                $argLists[] = [true, $this->createRequestWithMethodOverrideInTheBody($incompatibleRealMethod, $invalidOverride)];
                $argLists[] = [true, $this->createRequestWithMethodOverrideInTheQuery($incompatibleRealMethod, $invalidOverride)];
                $argLists[] = [true, $this->createRequestWithMethodOverrideInAHeader($incompatibleRealMethod, $invalidOverride)];
            }

            $argLists[] = [true, $this->createRequestWithMethodOverrideInTheBody($incompatibleRealMethod, $invalidOverrideInArray)];
            $argLists[] = [true, $this->createRequestWithMethodOverrideInTheQuery($incompatibleRealMethod, $invalidOverrideInArray)];

            foreach (InvalidSymfonyHttpMethodOverrideFilter::VALID_METHODS as $validOverride) {
                $argLists[] = [true, $this->createRequestWithMethodOverrideInTheBody($incompatibleRealMethod, $validOverride)];
                $argLists[] = [true, $this->createRequestWithMethodOverrideInTheQuery($incompatibleRealMethod, $validOverride)];
                $argLists[] = [true, $this->createRequestWithMethodOverrideInAHeader($incompatibleRealMethod, $validOverride)];
            }
        }

        $compatibleRealMethod = Request::METHOD_POST;

        foreach (InvalidSymfonyHttpMethodOverrideFilter::VALID_METHODS as $validOverride) {
            $argLists[] = [false, $this->createRequestWithMethodOverrideInTheBody($compatibleRealMethod, $validOverride)];
            $argLists[] = [false, $this->createRequestWithMethodOverrideInTheQuery($compatibleRealMethod, $validOverride)];
            $argLists[] = [false, $this->createRequestWithMethodOverrideInAHeader($compatibleRealMethod, $validOverride)];

            $anotherValidOverride = strtolower($validOverride);
            $argLists[] = [false, $this->createRequestWithMethodOverrideInTheBody($compatibleRealMethod, $anotherValidOverride)];
            $argLists[] = [false, $this->createRequestWithMethodOverrideInTheQuery($compatibleRealMethod, $anotherValidOverride)];
            $argLists[] = [false, $this->createRequestWithMethodOverrideInAHeader($compatibleRealMethod, $anotherValidOverride)];
        }

        // *Any* request without an override is okay, too :-)
        foreach (InvalidSymfonyHttpMethodOverrideFilter::VALID_METHODS as $validRealMethod) {
            $argLists[] = [false, $requestFactory->create(['method' => $validRealMethod])];
        }

        return $argLists;
    }

    /** @dataProvider providesRequests */
    public function testInvokeReturnsTrueIfTheMethodOverrideIsInvalid(
        bool $expected,
        Request $request
    ): void {
        $envelope = new Envelope($request, new NullLogger());
        $filter = new InvalidSymfonyHttpMethodOverrideFilter();

        $this->assertSame($expected, $filter($envelope));
    }

    /** @return array<mixed[]> */
    public function providesLogEntries(): array
    {
        return [
            [
                'The request contains invalid request-method overrides: `FOO`',
                $this->createRequestWithMethodOverrideInTheQuery(Request::METHOD_POST, 'foo'),
            ],
            [
                'The request contains invalid request-method overrides: `BAR`',
                $this->createRequestWithMethodOverrideInTheBody(Request::METHOD_POST, 'bar'),
            ],
            [
                'The request contains invalid request-method overrides: `BAZ`',
                $this->createRequestWithMethodOverrideInAHeader(Request::METHOD_POST, 'baz'),
            ],
            // An array is not permitted:
            [
                'The type of the request-method override is invalid',
                $this->createRequestWithMethodOverrideInTheQuery(Request::METHOD_POST, []),
            ],
            [
                'The type of the request-method override is invalid',
                $this->createRequestWithMethodOverrideInTheBody(Request::METHOD_POST, []),
            ],
        ];
    }

    /** @dataProvider providesLogEntries */
    public function testInvokeWillAddALogEntryIfTheRequestIsSuspicious(
        string $expectedLogMessage,
        Request $suspiciousRequest
    ): void {
        $envelope = new Envelope($suspiciousRequest, new NullLogger());

        $filterMock = $this
            ->getMockBuilder(InvalidSymfonyHttpMethodOverrideFilter::class)
            ->onlyMethods(['envelopeAddLogEntry'])
            ->getMock()
        ;

        $filterMock
            ->expects($this->once())
            ->method('envelopeAddLogEntry')
            ->with($envelope, $expectedLogMessage)
        ;

        $this->assertTrue($filterMock($envelope));
    }
}
