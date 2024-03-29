<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests;

use DanBettles\Defence\Envelope;
use DanBettles\Defence\Logger\NullLogger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;

class EnvelopeTest extends TestCase
{
    //###> Factory Methods ###
    private function createLoggerMock(): MockObject
    {
        return $this
            ->getMockBuilder(LoggerInterface::class)
            ->getMock()
        ;
    }
    //###< Factory Methods ###

    public function testIsInstantiable(): void
    {
        $request = Request::createFromGlobals();
        $logger = new NullLogger();

        $envelope = new Envelope($request, $logger);

        $this->assertSame($request, $envelope->getRequest());
        $this->assertSame($logger, $envelope->getLogger());
    }

    public function testImplementsPsrLoggerawareinterface(): void
    {
        $reflectionClass = new ReflectionClass(Envelope::class);

        $this->assertTrue($reflectionClass->implementsInterface(LoggerAwareInterface::class));
    }

    public function testAddlogAddsALogToTheLogger(): void
    {
        ($fullyLoadedRequestLoggerMock = $this->createLoggerMock())
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::WARNING, 'The request looks suspicious.', [
                'host_name' => gethostname(),
                'request_method' => 'GET',
                'uri' => 'http://foo.com/?bar=baz&qux=quux',
                'user_agent' => 'garply',
                'referer' => 'grault',
            ])
        ;

        /** @var LoggerInterface $fullyLoadedRequestLoggerMock */

        $fullyLoadedRequest = new Request([], [], [], [], [], [
            'HTTP_HOST' => 'foo.com',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => 'bar=baz&qux=quux',
            'HTTP_USER_AGENT' => 'garply',
            'HTTP_REFERER' => 'grault',
        ]);

        (new Envelope($fullyLoadedRequest, $fullyLoadedRequestLoggerMock))
            ->addLogEntry(LogLevel::WARNING, 'The request looks suspicious.')
        ;

        ($minimalRequestLoggerMock = $this->createLoggerMock())
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::EMERGENCY, 'The request looks suspicious.', [
                'host_name' => gethostname(),
                'request_method' => 'GET',
                'uri' => 'http://foo.com/?bar=baz&qux=quux',
            ])
        ;

        /** @var LoggerInterface $minimalRequestLoggerMock */

        $minimalRequest = new Request([], [], [], [], [], [
            'HTTP_HOST' => 'foo.com',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => 'bar=baz&qux=quux',
        ]);

        (new Envelope($minimalRequest, $minimalRequestLoggerMock))
            ->addLogEntry(LogLevel::EMERGENCY, 'The request looks suspicious.')
        ;

        ($postRequestLoggerMock = $this->createLoggerMock())
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::EMERGENCY, 'The request looks suspicious.', [
                'host_name' => gethostname(),
                'request_method' => 'POST',
                'uri' => 'http://foo.com/',
                'parameters' => [
                    'foo' => 'bar',
                    'baz' => 'qux',
                ],
            ])
        ;

        /** @var LoggerInterface $postRequestLoggerMock */

        $postRequest = new Request([], [
            'foo' => 'bar',
            'baz' => 'qux',
        ], [], [], [], [
            'HTTP_HOST' => 'foo.com',
            'REQUEST_METHOD' => 'POST',
        ]);

        (new Envelope($postRequest, $postRequestLoggerMock))
            ->addLogEntry(LogLevel::EMERGENCY, 'The request looks suspicious.')
        ;
    }
}
