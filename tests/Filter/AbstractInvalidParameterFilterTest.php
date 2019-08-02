<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Filter;

use PHPUnit\Framework\TestCase;
use ThreeStreams\Defence\Filter\AbstractInvalidParameterFilter;
use ThreeStreams\Defence\Filter\FilterInterface;
use ReflectionClass;

class AbstractInvalidParameterFilterTest extends TestCase
{
    public function testIsAbstract()
    {
        $reflectionClass = new ReflectionClass(AbstractInvalidParameterFilter::class);

        $this->assertTrue($reflectionClass->isAbstract());
    }

    public function testImplementsFilterinterface()
    {
        $reflectionClass = new ReflectionClass(AbstractInvalidParameterFilter::class);

        $this->assertTrue($reflectionClass->implementsInterface(FilterInterface::class));
    }
}
