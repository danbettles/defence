<?php

declare(strict_types=1);

namespace DanBettles\Defence\Handler;

use DanBettles\Defence\Envelope;

interface HandlerInterface
{
    /**
     * Does something about the fact the request is considered suspicious.
     */
    public function __invoke(Envelope $envelope);
}
