<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Filter;

use DanBettles\Defence\Envelope;
use DanBettles\Defence\Filter\AbstractFilter;
use DanBettles\Defence\Filter\InvalidSymfonyHttpMethodOverrideFilter;
use DanBettles\Defence\Logger\NullLogger;
use DanBettles\Defence\Tests\TestsFactory\RequestFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

use function is_subclass_of;

use const false;
use const true;

/**
 * Start here: https://github.com/symfony/symfony/blob/4.3/src/Symfony/Component/HttpFoundation/Request.php#L1231
 */
class InvalidSymfonyHttpMethodOverrideFilterTest extends TestCase
{
    public function testIsAnAbstractfilter()
    {
        $this->assertTrue(is_subclass_of(InvalidSymfonyHttpMethodOverrideFilter::class, AbstractFilter::class));
    }

    public function providesRequests(): array
    {
        $requestFactory = new RequestFactory();

        return [
            [true, $this->createPostRequestWithMethodOverrideInTheBody('foo')],
            [true, $this->createPostRequestWithMethodOverrideInTheBody('FOO')],
            [true, $this->createPostRequestWithMethodOverrideInTheBody('__construct')],

            [true, $this->createPostRequestWithMethodOverrideInTheQuery('foo')],
            [true, $this->createPostRequestWithMethodOverrideInTheQuery('FOO')],
            [true, $this->createPostRequestWithMethodOverrideInTheQuery('__construct')],

            [true, $this->createPostRequestWithMethodOverrideInAHeader('foo')],
            [true, $this->createPostRequestWithMethodOverrideInAHeader('FOO')],
            [true, $this->createPostRequestWithMethodOverrideInAHeader('__construct')],

            [false, $this->createPostRequestWithMethodOverrideInTheBody('HEAD')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('GET')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('POST')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('PUT')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('PATCH')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('DELETE')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('PURGE')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('OPTIONS')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('TRACE')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('CONNECT')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('head')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('get')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('post')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('put')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('patch')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('delete')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('purge')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('options')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('trace')],
            [false, $this->createPostRequestWithMethodOverrideInTheBody('connect')],

            [false, $this->createPostRequestWithMethodOverrideInTheQuery('HEAD')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('GET')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('POST')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('PUT')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('PATCH')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('DELETE')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('PURGE')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('OPTIONS')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('TRACE')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('CONNECT')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('head')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('get')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('post')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('put')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('patch')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('delete')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('purge')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('options')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('trace')],
            [false, $this->createPostRequestWithMethodOverrideInTheQuery('connect')],

            [false, $this->createPostRequestWithMethodOverrideInAHeader('HEAD')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('GET')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('POST')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('PUT')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('PATCH')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('DELETE')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('PURGE')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('OPTIONS')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('TRACE')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('CONNECT')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('head')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('get')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('post')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('put')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('patch')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('delete')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('purge')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('options')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('trace')],
            [false, $this->createPostRequestWithMethodOverrideInAHeader('connect')],

            [false, $this->createGetRequestWithMethodOverride('foo')],
            [false, $this->createGetRequestWithMethodOverride('FOO')],
            [false, $this->createGetRequestWithMethodOverride('__construct')],

            [false, $requestFactory->createGet()],
            [false, $requestFactory->createPost()],
        ];
    }

    /**
     * @dataProvider providesRequests
     */
    public function testInvokeReturnsTrueIfTheMethodOverrideIsInvalid($expected, $request)
    {
        $envelope = new Envelope($request, new NullLogger());
        $filter = new InvalidSymfonyHttpMethodOverrideFilter();

        $this->assertSame($expected, $filter($envelope));
    }

    public function testInvokeWillAddALogEntryIfTheRequestIsSuspicious()
    {
        $envelope = new Envelope(
            $this->createPostRequestWithMethodOverrideInTheQuery('foo'),
            new NullLogger()
        );

        $filterMock = $this
            ->getMockBuilder(InvalidSymfonyHttpMethodOverrideFilter::class)
            ->onlyMethods(['envelopeAddLogEntry'])
            ->getMock()
        ;

        $filterMock
            ->expects($this->once())
            ->method('envelopeAddLogEntry')
            ->with($envelope, 'The request contains invalid override methods: `FOO`')
        ;

        $this->assertTrue($filterMock($envelope));
    }

    //###> Factory Methods ###

    private function createPostRequestWithMethodOverrideInTheBody(string $overrideMethod): Request
    {
        return (new RequestFactory())->createPost([
            '_method' => $overrideMethod,
        ]);
    }

    private function createPostRequestWithMethodOverrideInTheQuery(string $overrideMethod): Request
    {
        $postRequest = (new RequestFactory())->createPost();
        $postRequest->query->set('_method', $overrideMethod);

        return $postRequest;
    }

    private function createPostRequestWithMethodOverrideInAHeader(string $overrideMethod): Request
    {
        $postRequest = (new RequestFactory())->createPost();
        $postRequest->headers->set('X-HTTP-METHOD-OVERRIDE', $overrideMethod);

        return $postRequest;
    }

    private function createGetRequestWithMethodOverride(string $overrideMethod): Request
    {
        return (new RequestFactory())->createGet([
            '_method' => $overrideMethod,
        ]);
    }

    //###< Factory Methods ###
}
