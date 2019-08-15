<?php declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use ThreeStreams\Defence\Factory\EnvelopeFactory;
use ThreeStreams\Defence\Envelope;
use ThreeStreams\Defence\Logger\NullLogger;

class EnvelopeFactoryTest extends TestCase
{
    public function testCreatedefaultReturnsAPreconfiguredEnvelope()
    {
        $envelope = (new EnvelopeFactory())->createDefaultEnvelope();

        $this->assertInstanceOf(Envelope::class, $envelope);

        $this->assertInstanceOf(Request::class, $envelope->getRequest());
        $this->assertInstanceOf(NullLogger::class, $envelope->getLogger());
    }
}
