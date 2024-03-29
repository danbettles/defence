<?php

declare(strict_types=1);

namespace DanBettles\Defence\Filter;

use Psr\Log\LogLevel;
use DanBettles\Defence\Envelope;

use function array_replace;

/**
 * The default log-level of a filter extending this class is `LogLevel::WARNING`.  That value can be overridden by
 * setting the `log_level` option.  The log-level is used by the add-log-entry convenience method.
 *
 * @phpstan-type IncomingFilterOptions array<string,string|null>
 * @phpstan-type BasicFilterOptions array{log_level:string}
 */
abstract class AbstractFilter implements FilterInterface
{
    /**
     * @phpstan-var BasicFilterOptions
     */
    private $options;

    /**
     * @phpstan-param IncomingFilterOptions $options
     */
    public function __construct(array $options = [])
    {
        /** @phpstan-var BasicFilterOptions */
        $completeOptions = array_replace([
            'log_level' => LogLevel::WARNING,
        ], $options);

        $this->setOptions($completeOptions);
    }

    /**
     * @phpstan-param BasicFilterOptions $options
     */
    private function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @phpstan-return BasicFilterOptions
     */
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
