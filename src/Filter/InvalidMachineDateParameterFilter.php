<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Filter;

/**
 * This filter will reject requests containing a date parameter whose value contains unexpected characters.  Unlike the
 * ISO 8601-format filter, which is very strict, it allows for slight variations in format and for typos.  It's purpose
 * is to prevent SQL injection.
 *
 * Allows dates formatted like "YYYY-MM-DD" or "DD-MM-YYYY".
 */
class InvalidMachineDateParameterFilter extends AbstractInvalidParameterFilter
{
    public function __construct(array $parameterNames, bool $allowBlank = true)
    {
        $this->setRegExp('/^[\d\-]+$/');

        parent::__construct($parameterNames, $allowBlank);
    }
}
