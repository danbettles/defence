<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Traversable;

use function is_array;

use const false;
use const true;

abstract class AbstractTestCase extends TestCase
{
    protected function assertContainsInstanceOf(string $expected, $collection): void
    {
        if (!is_array($collection) && !$collection instanceof Traversable) {
            throw new InvalidArgumentException('The collection is neither an array nor traversable.');
        }

        $containsInstanceOf = false;

        foreach ($collection as $element) {
            if ($element instanceof $expected) {
                $containsInstanceOf = true;
                break;
            }
        }

        $this->assertTrue($containsInstanceOf, "The collection does not contain an instance of `{$expected}`.");
    }
}
