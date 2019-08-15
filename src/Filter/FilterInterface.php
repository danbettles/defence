<?php declare(strict_types=1);

namespace ThreeStreams\Defence\Filter;

use ThreeStreams\Defence\Envelope;

interface FilterInterface
{
    /**
     * Returns `true` if the request looks suspicious, or `false` otherwise.
     */
    public function __invoke(Envelope $envelope): bool;
}
