<?php

declare(strict_types=1);

namespace DanBettles\Defence\Factory;

use DanBettles\Defence\Filter\InvalidHeaderFilter;
use DanBettles\Defence\Filter\InvalidParameterFilter;

use function preg_replace;

use const false;
use const null;

/**
 * Provides methods that create pre-configured filters.
 *
 * @phpstan-import-type Selector from InvalidParameterFilter
 *
 * @phpstan-type FactoryMethodOptions array<string,string|null>
 */
class FilterFactory
{
    /**
     * Creates a filter that will reject requests containing a date parameter whose value is not the correct shape.
     *
     * @phpstan-param Selector $selector
     * @phpstan-param FactoryMethodOptions $options
     */
    public function createInvalidIso8601DateParameterFilter($selector, array $options = []): InvalidParameterFilter
    {
        return new InvalidParameterFilter($selector, '/^(\d{4}-\d{2}-\d{2}|)$/', $options);
    }

    /**
     * This filter will reject requests containing a date parameter whose value contains unexpected characters.  Unlike
     * the ISO 8601-format filter, which is very strict, it allows for slight variations in format and for typos.  Its
     * purpose is to prevent SQL injection.
     *
     * Expects dates to be formatted like "YYYY-MM-DD" or "DD-MM-YYYY".
     *
     * @phpstan-param Selector $selector
     * @phpstan-param FactoryMethodOptions $options
     */
    public function createInvalidMachineDateParameterFilter($selector, array $options = []): InvalidParameterFilter
    {
        return new InvalidParameterFilter($selector, '/^[\d\-]*$/', $options);
    }

    /**
     * This filter will reject requests containing a numeric ID parameter whose value does not contain only digits or is
     * blank.
     *
     * Database ID (primary/foreign key) parameters are frequently targeted in SQL injection attacks.  Typically, these
     * sorts of database IDs are natural numbers, so that makes it very easy to identify suspicious requests.
     *
     * @phpstan-param Selector $selector
     * @phpstan-param FactoryMethodOptions $options
     */
    public function createInvalidNumericIdParameterFilter($selector, array $options = []): InvalidParameterFilter
    {
        return new InvalidParameterFilter($selector, '/^\d*$/', $options);
    }

    /**
     * When working with Symfony, it's sometimes convenient to be able to set the request-format through a parameter in
     * the request (e.g. `"/?_format=json"`).  We've seen malicious requests that contain potentially harmful, and
     * probing, values for the request format.
     *
     * Additional options:
     * - parameter_name: string
     *
     * @phpstan-param FactoryMethodOptions $options
     */
    public function createInvalidSymfonyRequestFormatFilter(array $options = []): InvalidParameterFilter
    {
        $selector = [
            ($options['parameter_name'] ?? '_format'),
        ];

        unset($options['parameter_name']);

        $options['type'] = InvalidParameterFilter::TYPE_STRING;

        return new InvalidParameterFilter($selector, '~^[a-zA-Z0-9\-]+$~', $options);
    }

    /**
     * This filter can help to safely eliminate a swathe of suspicious requests.
     *
     * It appears that all friendly user-agents, including browsers, bots, and command-line tools, are happy to identify
     * themselves -- indeed many now include their origin in their UA string.  We've seen plenty malicious requests with
     * a blank, or absent, user-agent header but never a benign request with no UA string.
     *
     * @phpstan-param FactoryMethodOptions $options
     */
    public function createSuspiciousUserAgentHeaderFilter(array $options = []): InvalidHeaderFilter
    {
        $validator = function (?string $uaString): bool {
            if (null === $uaString) {
                return false;
            }

            $significantChars = preg_replace('~[^a-zA-Z]~', '', $uaString);

            return '' !== $significantChars;
        };

        return new InvalidHeaderFilter('User-Agent', $validator, $options);
    }
}
