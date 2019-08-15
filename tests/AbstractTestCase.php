<?php declare(strict_types=1);

namespace ThreeStreams\Defence\Tests;

use PHPUnit\Framework\TestCase;
use Traversable;
use InvalidArgumentException;

abstract class AbstractTestCase extends TestCase
{
    protected function assertContainsInstanceOf(string $expected, $collection): void
    {
        if (!\is_array($collection) && !$collection instanceof Traversable) {
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
