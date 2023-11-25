<?php

declare(strict_types=1);

namespace DanBettles\Defence;

/**
 * Wraps a selection of PHP functions so that we can work with mock objects in tests and test otherwise untestable code.
 */
class PhpFunctionsWrapper
{
    /**
     * See https://www.php.net/manual/en/function.exit.php
     *
     * @param string|int $status
     * @return never
     */
    public function exit($status = 0)
    {
        //@codingStandardsIgnoreStart
        exit($status);
        //@codingStandardsIgnoreEnd
    }
}
