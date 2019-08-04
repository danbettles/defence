<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;

/**
 * A logger that does nothing.
 *
 * Use this logger if you aren't interested in what the filters or handler get up to.  You might use it if, for example,
 * you're using _Defence_ as an intrusion _prevention_ system and want only to reject suspicious requests.
 */
class NullLogger extends AbstractLogger
{
    /**
     * {@inheritDoc}
     * @see LoggerInterface::log()
     */
    public function log($level, $message, array $context = array())
    {
    }
}
