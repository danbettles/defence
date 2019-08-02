<?php
declare(strict_types=1);

namespace ThreeStreams\Defence;

use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;

/**
 * A very basic logger that keeps logs in memory, in an array.
 */
class Logger extends AbstractLogger
{
    /**
     * All logs, categorized by level.
     *
     * @var array
     */
    private $logs;

    public function __construct()
    {
        $this->setLogs([]);
    }

    private function setLogs(array $logs): self
    {
        $this->logs = $logs;
        return $this;
    }

    /**
     * Returns an array containing all logs, which are categorized by level.
     *
     * @return array
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * {@inheritDoc}
     * @see LoggerInterface::log()
     */
    public function log($level, $message, array $context = array())
    {
        $this->logs[$level][] = [
            'message' => $message,
            'context' => $context,
        ];
    }
}
