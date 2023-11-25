<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests;

use DanBettles\Defence\Tests\TestsFactory\RequestFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Traversable;

use function is_array;

use const false;
use const true;

abstract class AbstractTestCase extends TestCase
{
    protected function getRequestFactory(): RequestFactory
    {
        return new RequestFactory();
    }

    /**
     * @phpstan-param class-string $expectedClassName
     * @phpstan-param class-string|object $objectOrClassName
     */
    protected function assertSubclassOf(string $expectedClassName, $objectOrClassName): void
    {
        $reflectionClass = new ReflectionClass($objectOrClassName);

        $this->assertTrue($reflectionClass->isSubclassOf($expectedClassName));
    }

    /**
     * @param mixed $collection
     * @throws InvalidArgumentException If the collection is neither an array nor traversable
     */
    protected function assertContainsInstanceOf(string $expected, $collection): void
    {
        if (!is_array($collection) && !($collection instanceof Traversable)) {
            throw new InvalidArgumentException('The collection is neither an array nor traversable');
        }

        $containsInstanceOf = false;

        foreach ($collection as $element) {
            if ($element instanceof $expected) {
                $containsInstanceOf = true;

                break;
            }
        }

        $this->assertTrue($containsInstanceOf, "The collection does not contain an instance of `{$expected}`");
    }
}
