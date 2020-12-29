<?php

declare(strict_types=1);

namespace ThreeStreams\Defence\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use ThreeStreams\Defence\Logger\NullLogger;
use ThreeStreams\Defence\Envelope;
use ReflectionClass;

class EnvelopeTest extends TestCase
{
    public function testIsInstantiable()
    {
        $request = Request::createFromGlobals();
        $logger = new NullLogger();

        $envelope = new Envelope($request, $logger);

        $this->assertSame($request, $envelope->getRequest());
        $this->assertSame($logger, $envelope->getLogger());
    }

    public function testImplementsPsrLoggerawareinterface()
    {
        $reflectionClass = new ReflectionClass(Envelope::class);

        $this->assertTrue($reflectionClass->implementsInterface(LoggerAwareInterface::class));
    }

    public function testAddlogAddsALogToTheLogger()
    {
        $fullyLoadedLoggerMock = $this
            ->getMockBuilder(LoggerInterface::class)
            ->getMock()
        ;

        $fullyLoadedLoggerMock
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::WARNING, 'The request looks suspicious.', [
                'host_name' => gethostname(),
                'uri' => 'http://foo.com/?bar=baz&qux=quux',
                'user_agent' => 'garply',
                'referer' => 'grault',
            ])
        ;

        $fullyLoadedRequest = new Request([], [], [], [], [], [
            'HTTP_HOST' => 'foo.com',
            'QUERY_STRING' => 'bar=baz&qux=quux',
            'HTTP_USER_AGENT' => 'garply',
            'HTTP_REFERER' => 'grault',
        ]);

        $fullyLoadedEnvelope = new Envelope($fullyLoadedRequest, $fullyLoadedLoggerMock);
        $fullyLoadedEnvelope->addLogEntry(LogLevel::WARNING, 'The request looks suspicious.');

        $minimalLoggerMock = $this
            ->getMockBuilder(LoggerInterface::class)
            ->getMock()
        ;

        $minimalLoggerMock
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::EMERGENCY, 'The request looks suspicious.', [
                'host_name' => gethostname(),
                'uri' => 'http://foo.com/?bar=baz&qux=quux',
            ])
        ;

        $minimalRequest = new Request([], [], [], [], [], [
            'HTTP_HOST' => 'foo.com',
            'QUERY_STRING' => 'bar=baz&qux=quux',
        ]);

        $minimalEnvelope = new Envelope($minimalRequest, $minimalLoggerMock);
        $minimalEnvelope->addLogEntry(LogLevel::EMERGENCY, 'The request looks suspicious.');
    }
}
