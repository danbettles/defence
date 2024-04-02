<?php

declare(strict_types=1);

namespace DanBettles\Defence\Logger;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * A logger that does nothing.
 *
 * Use this logger if you aren't interested in what the filters or handler get up to.  You might use it if, for example,
 * you're using Defence as an intrusion _prevention_ system and want only to reject suspicious requests.
 *
 * @phpstan-type Context array<string,mixed>
 */
class NullLogger extends AbstractLogger
{
    /**
     * @override
     * @phpstan-param Context $context
     */
    public function log(
        $level,
        string|Stringable $message,
        array $context = []
    ): void {
    }
}
