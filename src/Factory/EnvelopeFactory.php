<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Factory;

use Symfony\Component\HttpFoundation\Request;
use ThreeStreams\Defence\Envelope;
use ThreeStreams\Defence\Logger\Logger;

class EnvelopeFactory
{
    /**
     * Creates an envelope containing a _Symfony_ HTTPFoundation `Request` and an instance of the _Defence_ logger,
     * which is good enough for simply getting information from the filters to the handler.
     */
    public function createDefault(): Envelope
    {
        return new Envelope(Request::createFromGlobals(), new Logger());
    }
}
