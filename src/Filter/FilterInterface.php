<?php

declare(strict_types=1);

namespace DanBettles\Defence\Filter;

use DanBettles\Defence\Envelope;

interface FilterInterface
{
    /**
     * Returns `true` if the request looks suspicious, or `false` otherwise.
     */
    public function __invoke(Envelope $envelope): bool;
}
