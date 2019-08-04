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
        $loggerMock = $this
            ->getMockBuilder(LoggerInterface::class)
            ->getMock()
        ;

        $loggerMock
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::WARNING, 'The request looks suspicious.', [
                'host_name' => gethostname(),
                'uri' => 'http://foo.com/?bar=baz&qux=quux',
            ])
        ;

        $request = new Request([], [], [], [], [], [
            'HTTP_HOST' => 'foo.com',
            'QUERY_STRING' => 'bar=baz&qux=quux',
        ]);

        $envelope = new Envelope($request, $loggerMock);
        $something = $envelope->addLog('The request looks suspicious.');

        $this->assertSame($envelope, $something);
    }
}
