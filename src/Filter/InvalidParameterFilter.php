<?php

declare(strict_types=1);

namespace DanBettles\Defence\Filter;

use DanBettles\Defence\Envelope;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

use function array_flip;
use function array_intersect_key;
use function is_array;
use function is_string;
use function preg_match;

use const false;
use const true;

/**
 * This filter can be used to reject requests containing a parameter with a suspicious value.  Its principal purpose is
 * to prevent SQL injection.
 *
 * There are a number of different types of parameter that can be easily vetted without in-depth knowledge of how
 * they're used.  For example, most -- if not all -- database ID (primary/foreign key) parameters, which are frequently
 * targeted in SQL injection attacks, should contain only digits.  Similarly, if you use the ISO 8601 format to express
 * dates then you're expecting only digits and dashes.  Consider the conventions you employ in your own application.
 *
 * There are two ways to select the parameters to examine: pass an array of names; or specify a regex that will match
 * the names of parameters of interest.  The second approach is especially useful if you follow strict naming
 * conventions.  For example, if you follow the old Ruby on Rails convention of using the suffixes "_on" and "_at" to
 * indicate date/times then you could easily select date parameters.
 *
 * The validity of the value is determined using the 'validator', a regular expression that matches a _valid_ value.
 *
 * @phpstan-import-type FilterOptions from \DanBettles\Defence\Filter\AbstractFilter
 * @phpstan-type Selector string|string[]
 * @phpstan-type RequestParameters array<string,string>
 * @phpstan-type GroupedParameters array{query?:RequestParameters,request?:RequestParameters}
 */
class InvalidParameterFilter extends AbstractFilter
{
    /**
     * @phpstan-var Selector
     */
    private $selector;

    /**
     * @var string
     */
    private $validator;

    /**
     * @phpstan-param Selector $selector
     * @param string $validator
     * @phpstan-param FilterOptions $options
     */
    public function __construct($selector, string $validator, array $options = [])
    {
        parent::__construct($options);

        $this
            ->setSelector($selector)
            ->setValidator($validator)
        ;
    }

    /**
     * @phpstan-param Selector $selector
     * @throws InvalidArgumentException If the selector is invalid.
     */
    private function setSelector($selector): self
    {
        /** @phpstan-ignore-next-line */
        if (!is_array($selector) && !is_string($selector)) {
            throw new InvalidArgumentException('The selector is invalid.');
        }

        $this->selector = $selector;

        return $this;
    }

    /**
     * @phpstan-return Selector
     */
    public function getSelector()
    {
        return $this->selector;
    }

    private function setValidator(string $validator): self
    {
        $this->validator = $validator;

        return $this;
    }

    public function getValidator(): string
    {
        return $this->validator;
    }

    /**
     * Returns an array containing all request parameters, grouped by parameter-bag name.
     *
     * @phpstan-return GroupedParameters
     */
    private function requestGetParametersGrouped(Request $request): array
    {
        $grouped = [];

        foreach (['query', 'request'] as $paramBagName) {
            $grouped[$paramBagName] = $request->{$paramBagName}->all();
        }

        return $grouped;
    }

    /**
     * Filters the request parameters using the selector; returns an array containing the relevant request parameters
     * grouped by parameter-bag name
     *
     * @phpstan-return GroupedParameters
     */
    private function filterRequestParametersBySelector(Request $request): array
    {
        if (is_array($this->getSelector())) {
            $selectorParamNamesAsKeys = array_flip($this->getSelector());

            $relevantParameters = [];

            foreach ($this->requestGetParametersGrouped($request) as $paramBagName => $parameters) {
                $relevantParameters[$paramBagName] = array_intersect_key($parameters, $selectorParamNamesAsKeys);
            }

            return $relevantParameters;
        }

        $relevantParameters = [];

        foreach ($this->requestGetParametersGrouped($request) as $paramBagName => $parameters) {
            foreach ($parameters as $paramName => $paramValue) {
                if (preg_match($this->getSelector(), (string) $paramName)) {
                    $relevantParameters[$paramBagName][$paramName] = $paramValue;
                }
            }
        }

        return $relevantParameters;
    }

    /**
     * Returns `true` if the parameter is valid, or `false` otherwise.  Additionally, a message will be added to the
     * log, accessed via the envelope, if the parameter is invalid.
     */
    private function validateParameterValue(
        Envelope $envelope,
        string $paramBagName,
        string $paramName,
        string $paramValue
    ): bool {
        if (preg_match($this->getValidator(), $paramValue)) {
            return true;
        }

        $this->envelopeAddLogEntry(
            $envelope,
            "The value of `{$paramBagName}.{$paramName}` failed validation using the regex `{$this->getValidator()}`."
        );

        return false;
    }

    public function __invoke(Envelope $envelope): bool
    {
        $filteredParamsByBagName = $this->filterRequestParametersBySelector($envelope->getRequest());

        foreach ($filteredParamsByBagName as $paramBagName => $parameters) {
            foreach ($parameters as $paramName => $maybeAnArrayOfValues) {
                foreach ((array) $maybeAnArrayOfValues as $paramValue) {
                    if (!$this->validateParameterValue($envelope, $paramBagName, $paramName, $paramValue)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
