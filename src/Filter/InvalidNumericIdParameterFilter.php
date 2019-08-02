<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Filter;

/**
 * This filter will reject requests containing a numeric ID parameter whose value does not contain only digits or,
 * optionally, is blank.  The names of ID parameters to check are passed to the constructor.
 *
 * Database ID (primary/foreign key) parameters are frequently targeted in SQL injection attacks.  Typically, these
 * sorts of database IDs are natural numbers, so that makes it very easy to identify suspicious requests.
 */
class InvalidNumericIdParameterFilter extends AbstractInvalidParameterFilter
{
    public function __construct(array $parameterNames, bool $allowBlank = true)
    {
        $this->setRegExp('/^\d+$/');

        parent::__construct($parameterNames, $allowBlank);
    }
}
