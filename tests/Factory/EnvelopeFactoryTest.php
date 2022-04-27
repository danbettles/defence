<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use DanBettles\Defence\Factory\EnvelopeFactory;
use DanBettles\Defence\Envelope;
use DanBettles\Defence\Logger\NullLogger;

class EnvelopeFactoryTest extends TestCase
{
    public function testCreatedefaultenvelopeReturnsAPreconfiguredEnvelope()
    {
        $envelope = (new EnvelopeFactory())->createDefaultEnvelope();

        $this->assertInstanceOf(Envelope::class, $envelope);

        $this->assertInstanceOf(Request::class, $envelope->getRequest());
        $this->assertInstanceOf(NullLogger::class, $envelope->getLogger());
    }
}
