<?php

declare(strict_types=1);

namespace DanBettles\Defence\Factory;

use DanBettles\Defence\Filter\InvalidParameterFilter;

/**
 * Provides methods that create pre-configured filters.
 *
 * @phpstan-import-type Selector from InvalidParameterFilter
 * @phpstan-import-type FilterOptions from \DanBettles\Defence\Filter\AbstractFilter
 */
class FilterFactory
{
    /**
     * Creates a filter that will reject requests containing a date parameter whose value is not the correct shape.
     *
     * @phpstan-param Selector $selector
     * @phpstan-param FilterOptions $options
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
     * @phpstan-param FilterOptions $options
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
     * @phpstan-param FilterOptions $options
     */
    public function createInvalidNumericIdParameterFilter($selector, array $options = []): InvalidParameterFilter
    {
        return new InvalidParameterFilter($selector, '/^\d*$/', $options);
    }
}
