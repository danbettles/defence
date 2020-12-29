<?php

declare(strict_types=1);

namespace ThreeStreams\Defence\Handler;

use ThreeStreams\Defence\Envelope;

interface HandlerInterface
{
    /**
     * Does something about the fact the request is considered suspicious.
     */
    public function __invoke(Envelope $envelope);
}
