<?php declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Filter;

use PHPUnit\Framework\TestCase;
use ThreeStreams\Defence\Filter\AbstractFilter;
use ThreeStreams\Defence\Filter\FilterInterface;
use ReflectionClass;

class AbstractFilterTest extends TestCase
{
    public function testIsAbstract()
    {
        $reflectionClass = new ReflectionClass(AbstractFilter::class);

        $this->assertTrue($reflectionClass->isAbstract());
    }

    public function testImplementsFilterinterface()
    {
        $reflectionClass = new ReflectionClass(AbstractFilter::class);

        $this->assertTrue($reflectionClass->implementsInterface(FilterInterface::class));
    }

    public function testConstructor()
    {
        $filterMock = $this->getMockForAbstractClass(AbstractFilter::class, [
            'options' => ['foo' => 'bar', 'baz' => 'qux'],
        ]);

        $this->assertSame([
            'foo' => 'bar',
            'baz' => 'qux',
        ], $filterMock->getOptions());
    }

    public function testOptionsAreOptional()
    {
        $filterMock = $this->getMockForAbstractClass(AbstractFilter::class);

        $this->assertSame([], $filterMock->getOptions());
    }
}
