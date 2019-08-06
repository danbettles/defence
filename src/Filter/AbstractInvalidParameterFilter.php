<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Filter;

use ThreeStreams\Defence\Envelope;
use LogicException;

abstract class AbstractInvalidParameterFilter implements FilterInterface
{
    /** @var string */
    private $regExp;

    /** @var array */
    private $parameterNames;

    /** @var bool */
    private $allowBlank;

    public function __construct(array $parameterNames, bool $allowBlank = true)
    {
        $this
            ->setParameterNames($parameterNames)
            ->setAllowBlank($allowBlank)
        ;
    }

    protected function setRegExp(string $regExp): self
    {
        $this->regExp = $regExp;
        return $this;
    }

    /**
     * @throws LogicException There is no regular expression.  Concrete subclasses must provide a regular expression.
     */
    protected function getRegExp(): string
    {
        if (!\is_string($this->regExp) || '' === $this->regExp) {
            throw new LogicException(
                'There is no regular expression.  Concrete subclasses must provide a regular expression.'
            );
        }

        return $this->regExp;
    }

    private function setParameterNames(array $parameterNames): self
    {
        $this->parameterNames = $parameterNames;
        return $this;
    }

    public function getParameterNames(): array
    {
        return $this->parameterNames;
    }

    private function setAllowBlank(bool $allowBlank): self
    {
        $this->allowBlank = $allowBlank;
        return $this;
    }

    public function getAllowBlank(): bool
    {
        return $this->allowBlank;
    }

    /**
     * Convenience method that adds a log to the logger in the specified envelope.
     */
    private function envelopeAddLog(Envelope $envelope, string $parameterName, string $value): void
    {
        $envelope->addLog("The value of `{$parameterName}` does not match the regex `{$this->getRegExp()}`.");
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(Envelope $envelope): bool
    {
        $idParamNamesAsKeys = \array_flip($this->getParameterNames());

        foreach (['query', 'request'] as $paramBagName) {
            $parameters = $envelope->getRequest()->{$paramBagName}->all();
            $relevantParameters = \array_intersect_key($parameters, $idParamNamesAsKeys);

            foreach ($relevantParameters as $paramName => $maybeAnArrayOfValues) {
                foreach ((array) $maybeAnArrayOfValues as $paramValue) {
                    if ('' === $paramValue && $this->getAllowBlank()) {
                        continue;
                    }

                    if (!\preg_match($this->getRegExp(), $paramValue)) {
                        $this->envelopeAddLog($envelope, "{$paramBagName}.{$paramName}", $paramValue);
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
