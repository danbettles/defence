<?php declare(strict_types=1);

namespace ThreeStreams\Defence\Filter;

use Psr\Log\LogLevel;
use ThreeStreams\Defence\Envelope;

/**
 * The default log-level of a filter extending this class is `LogLevel::WARNING`.  That value can be overridden by
 * setting the `log_level` option.  The log-level is used by the add-log-entry convenience method.
 */
abstract class AbstractFilter implements FilterInterface
{
    /** @var array */
    private $options;

    public function __construct(array $options = [])
    {
        $this->setOptions(\array_replace([
            'log_level' => LogLevel::WARNING,
        ], $options));
    }

    private function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Adds a log entry to the log accessed via the envelope.
     */
    protected function envelopeAddLogEntry(Envelope $envelope, string $message): void
    {
        $envelope->addLogEntry($this->getOptions()['log_level'], $message);
    }
}
