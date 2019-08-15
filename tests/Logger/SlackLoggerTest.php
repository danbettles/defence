<?php declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Logger;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use ThreeStreams\Defence\Logger\SlackLogger;
use InvalidArgumentException;
use Exception;

class SlackLoggerTest extends TestCase
{
    public function testIsAPsrAbstractlogger()
    {
        $this->assertTrue(is_subclass_of(SlackLogger::class, AbstractLogger::class));
    }

    public function testConstructor()
    {
        $config = [
            'webhook_url' => 'https://hooks.slack.com/services/foo/bar/baz',
        ];

        $slackLogger = new SlackLogger($config);

        $this->assertSame($config, $slackLogger->getConfig());
    }

    public function testConstructorThrowsAnExceptionIfTheConfigIsIncomplete()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('`webhook_url` is missing.');

        new SlackLogger([]);
    }

    public function providesLogMethodArguments(): array
    {
        return [[
            LogLevel::EMERGENCY,
            'message' => 'System is unusable.',
            'context' => ['foo' => 'bar'],
            'emergency',
        ], [
            LogLevel::ALERT,
            'message' => 'Action must be taken immediately.',
            'context' => ['baz' => 'qux'],
            'alert',
        ], [
            LogLevel::CRITICAL,
            'message' => 'Critical condition.',
            'context' => ['quux' => 'quuz'],
            'critical',
        ], [
            LogLevel::ERROR,
            'message' => 'Runtime error that does not require immediate action.',
            'context' => ['corge' => 'grault'],
            'error',
        ], [
            LogLevel::WARNING,
            'message' => 'Exceptional occurrence that is not an error.',
            'context' => ['garply' => 'waldo'],
            'warning',
        ], [
            LogLevel::NOTICE,
            'message' => 'Normal but significant event.',
            'context' => ['fred' => 'plugh'],
            'notice',
        ], [
            LogLevel::INFO,
            'message' => 'Interesting event.',
            'context' => ['xyzzy' => 'thud'],
            'info',
        ], [
            LogLevel::DEBUG,
            'message' => 'Detailed debug information.',
            'context' => ['wibble' => 'wobble'],
            'debug',
        ]];
    }

    /**
     * @dataProvider providesLogMethodArguments
     */
    public function testAllLogMethodsCallLog($level, $message, $context, $loggerMethodName)
    {
        $slackLoggerMock = $this
            ->getMockBuilder(SlackLogger::class)
            ->setConstructorArgs([
                ['webhook_url' => 'https://hooks.slack.com/services/foo/bar/baz'],
            ])
            ->setMethods(['log'])
            ->getMock()
        ;

        $slackLoggerMock
            ->expects($this->once())
            ->method('log')
            ->with($level, $message, $context)
        ;

        $slackLoggerMock->{$loggerMethodName}($message, $context);
    }

    public function testLogSendsAMessageToSlack()
    {
        $level = LogLevel::INFO;
        $message = 'Interesting event.';
        $context = ['xyzzy' => 'thud'];

        //@codingStandardsIgnoreStart
        $contextFormatted = print_r($context, true);
        //@codingStandardsIgnoreEnd

        $expectedJson = json_encode([
            'text' => "*Level*\n{$level}\n*Message*\n{$message}\n*Context*\n```{$contextFormatted}```",
        ]);

        $slackLoggerMock = $this
            ->getMockBuilder(SlackLogger::class)
            ->setConstructorArgs([['webhook_url' => 'https://hooks.slack.com/services/foo/bar/baz']])
            ->setMethods(['sendJsonToSlack'])
            ->getMock()
        ;

        $slackLoggerMock
            ->expects($this->once())
            ->method('sendJsonToSlack')
            ->with($expectedJson)
        ;

        $slackLoggerMock->log($level, $message, $context);
    }

    public function testLogThrowsAnExceptionIfItFailedToCommunicateWithSlack()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to send the JSON to Slack.');

        $logger = new SlackLogger(['webhook_url' => 'http://localhost']);
        $logger->log(LogLevel::DEBUG, 'bar');
    }

    public function testConstructorThrowsAnExceptionIfTheWebhookUrlIsNotAValidUrl()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The webhook URL is not a valid URL.');

        new SlackLogger(['webhook_url' => 'foo']);
    }
}
