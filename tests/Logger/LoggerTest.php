<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Logger;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use ThreeStreams\Defence\Logger\Logger;

class LoggerTest extends TestCase
{
    public function testIsAPsrAbstractlogger()
    {
        $this->assertTrue(is_subclass_of(Logger::class, AbstractLogger::class));
    }

    public function testAddsLogs()
    {
        $logger = new Logger();
        $logger->emergency('System is unusable.', ['foo' => 'bar']);
        $logger->alert('Action must be taken immediately.', ['baz' => 'qux']);
        $logger->critical('Critical conditions.', ['quux' => 'quuz']);
        $logger->error('Runtime errors that do not require immediate action.', ['corge' => 'grault']);
        $logger->warning('Exceptional occurrences that are not errors.', ['garply' => 'waldo']);
        $logger->notice('Normal but significant events.', ['fred' => 'plugh']);
        $logger->info('Interesting events.', ['xyzzy' => 'thud']);
        $logger->debug('Detailed debug information.', ['wibble' => 'wobble']);
        $logger->log(LogLevel::WARNING, 'Another warning.', ['wubble' => 'flob']);

        $this->assertSame([
            LogLevel::EMERGENCY => [[
                'message' => 'System is unusable.',
                'context' => ['foo' => 'bar'],
            ]],
            LogLevel::ALERT => [[
                'message' => 'Action must be taken immediately.',
                'context' => ['baz' => 'qux'],
            ]],
            LogLevel::CRITICAL => [[
                'message' => 'Critical conditions.',
                'context' => ['quux' => 'quuz'],
            ]],
            LogLevel::ERROR => [[
                'message' => 'Runtime errors that do not require immediate action.',
                'context' => ['corge' => 'grault'],
            ]],
            LogLevel::WARNING => [[
                'message' => 'Exceptional occurrences that are not errors.',
                'context' => ['garply' => 'waldo'],
            ], [
                'message' => 'Another warning.',
                'context' => ['wubble' => 'flob'],
            ]],
            LogLevel::NOTICE => [[
                'message' => 'Normal but significant events.',
                'context' => ['fred' => 'plugh'],
            ]],
            LogLevel::INFO => [[
                'message' => 'Interesting events.',
                'context' => ['xyzzy' => 'thud'],
            ]],
            LogLevel::DEBUG => [[
                'message' => 'Detailed debug information.',
                'context' => ['wibble' => 'wobble'],
            ]],
        ], $logger->getLogs());
    }
}
