<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Filter;

/**
 * This filter will reject requests containing a date parameter whose value is not the correct shape.  It doesn't go as
 * far as validate the date because it's purpose is only to identify requests that look suspect.
 *
 * We often see date parameters targeted in SQL injection attacks.  We always use the ISO 8601 date format to move dates
 * around, so that makes it very easy to identify suspicious requests.
 */
class InvalidIso8601DateParameterFilter extends AbstractInvalidParameterFilter
{
    public function __construct(array $parameterNames, bool $allowBlank = true)
    {
        $this->setRegExp('/^\d{4}-\d{2}-\d{2}$/');  //Just the shape.

        parent::__construct($parameterNames, $allowBlank);
    }
}
