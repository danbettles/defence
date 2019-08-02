<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LogLevel;
use ThreeStreams\Defence\Logger;
use ThreeStreams\Defence\Envelope;
use ReflectionClass;

class EnvelopeTest extends TestCase
{
    public function testIsInstantiable()
    {
        $request = Request::createFromGlobals();
        $logger = new Logger();

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
        $logger = new Logger();

        $request = new Request([], [], [], [], [], [
            'HTTP_HOST' => 'foo.com',
            'QUERY_STRING' => 'bar=baz&qux=quux',
        ]);

        $envelope = new Envelope($request, $logger);
        $something = $envelope->addLog('The request looks suspicious.');

        $this->assertSame([
            LogLevel::WARNING => [[
                'message' => 'The request looks suspicious.',
                'context' => ['uri' => 'http://foo.com/?bar=baz&qux=quux'],
            ]],
        ], $envelope->getLogger()->getLogs());

        $this->assertSame($envelope, $something);
    }
}
