<?php

declare(strict_types=1);

namespace DanBettles\Defence\Filter;

use Closure;
use DanBettles\Defence\Envelope;

use const false;
use const true;

/**
 * @phpstan-import-type IncomingFilterOptions from AbstractFilter
 */
class InvalidHeaderFilter extends AbstractFilter
{
    /**
     * @phpstan-param IncomingFilterOptions $options
     */
    public function __construct(
        private string $selector,
        private Closure $validator,
        array $options = []
    ) {
        parent::__construct($options);
    }

    public function __invoke(Envelope $envelope): bool
    {
        $request = $envelope->getRequest();
        $headerValue = $request->headers->get($this->getSelector());
        $headerValueIsValid = ($this->getValidator())($headerValue);

        if (!$headerValueIsValid) {
            $this->envelopeAddLogEntry(
                $envelope,
                "The value of header `{$this->getSelector()}`, `{$headerValue}`, is invalid"
            );

            return true;
        }

        return false;
    }

    public function getSelector(): string
    {
        return $this->selector;
    }

    public function getValidator(): Closure
    {
        return $this->validator;
    }
}
