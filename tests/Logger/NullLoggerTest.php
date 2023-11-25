<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Logger;

use DanBettles\Defence\Logger\NullLogger;
use DanBettles\Defence\Tests\AbstractTestCase;
use Psr\Log\AbstractLogger;
use ReflectionClass;

class NullLoggerTest extends AbstractTestCase
{
    public function testIsAPsrAbstractlogger(): void
    {
        $this->assertSubclassOf(AbstractLogger::class, NullLogger::class);
    }

    public function testIsConcrete(): void
    {
        $reflectionClass = new ReflectionClass(NullLogger::class);

        $this->assertFalse($reflectionClass->isAbstract());
    }
}
