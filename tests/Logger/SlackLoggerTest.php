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
        $slackLogger = new SlackLogger([
            'webhook_url' => 'https://hooks.slack.com/services/foo/bar/baz',
        ]);

        $this->assertSame([
            'min_log_level' => LogLevel::DEBUG,
            'webhook_url' => 'https://hooks.slack.com/services/foo/bar/baz',
        ], $slackLogger->getConfig());
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

    public function testLogWillSendAMessageToSlack()
    {
        $level = LogLevel::ERROR;
        $message = 'Runtime error that does not require immediate action.';
        $context = ['xyzzy' => 'thud'];

        $expectedJson = \json_encode([
            'text' => ":bangbang: Defence handled suspicious request",
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => 'Handled suspicious request.',
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => '*:bangbang: Error: Runtime error that does not require immediate action.*',
                    ],
                ],
                [
                    'type' => 'context',
                    'elements' => [
                        [
                            'type' => 'mrkdwn',
                            'text' => "xyzzy: thud",
                        ],
                    ],
                ],
            ],
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
        $logger->log(LogLevel::ERROR, 'bar');
    }

    public function testConstructorThrowsAnExceptionIfTheWebhookUrlIsNotAValidUrl()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The webhook URL is not a valid URL.');

        new SlackLogger(['webhook_url' => 'foo']);
    }

    public function providesLogLevelsThatWillTriggerAction(): array
    {
        return [[
            'logLevel' => LogLevel::DEBUG,
            'minLogLevel' => LogLevel::DEBUG,
        ], [
            'logLevel' => LogLevel::INFO,
            'minLogLevel' => LogLevel::DEBUG,
        ], [
            'logLevel' => LogLevel::INFO,
            'minLogLevel' => LogLevel::INFO,
        ], [
            'logLevel' => LogLevel::NOTICE,
            'minLogLevel' => LogLevel::INFO,
        ], [
            'logLevel' => LogLevel::NOTICE,
            'minLogLevel' => LogLevel::NOTICE,
        ], [
            'logLevel' => LogLevel::WARNING,
            'minLogLevel' => LogLevel::NOTICE,
        ], [
            'logLevel' => LogLevel::WARNING,
            'minLogLevel' => LogLevel::WARNING,
        ], [
            'logLevel' => LogLevel::ERROR,
            'minLogLevel' => LogLevel::WARNING,
        ], [
            'logLevel' => LogLevel::ERROR,
            'minLogLevel' => LogLevel::ERROR,
        ], [
            'logLevel' => LogLevel::CRITICAL,
            'minLogLevel' => LogLevel::ERROR,
        ], [
            'logLevel' => LogLevel::CRITICAL,
            'minLogLevel' => LogLevel::CRITICAL,
        ], [
            'logLevel' => LogLevel::ALERT,
            'minLogLevel' => LogLevel::CRITICAL,
        ], [
            'logLevel' => LogLevel::ALERT,
            'minLogLevel' => LogLevel::ALERT,
        ], [
            'logLevel' => LogLevel::EMERGENCY,
            'minLogLevel' => LogLevel::ALERT,
        ], [
            'logLevel' => LogLevel::EMERGENCY,
            'minLogLevel' => LogLevel::EMERGENCY,
        ]];
    }

    /**
     * @dataProvider providesLogLevelsThatWillTriggerAction
     */
    public function testLogWillSendAMessageToSlackIfTheLogLevelIsHighEnough($logLevel, $minLogLevel)
    {
        $slackLoggerMock = $this
            ->getMockBuilder(SlackLogger::class)
            ->setConstructorArgs([
                [
                    'webhook_url' => 'https://hooks.slack.com/services/foo/bar/baz',
                    'min_log_level' => $minLogLevel,
                ],
            ])
            ->setMethods(['sendJsonToSlack'])
            ->getMock()
        ;

        $slackLoggerMock
            ->expects($this->once())
            ->method('sendJsonToSlack')
        ;

        $slackLoggerMock->log($logLevel, 'Foo');
    }

    public function providesLogLevelsThatWillNotTriggerAction(): array
    {
        return [[
            'logLevel' => LogLevel::DEBUG,
            'minLogLevel' => LogLevel::INFO,
        ], [
            'logLevel' => LogLevel::INFO,
            'minLogLevel' => LogLevel::NOTICE,
        ], [
            'logLevel' => LogLevel::NOTICE,
            'minLogLevel' => LogLevel::WARNING,
        ], [
            'logLevel' => LogLevel::WARNING,
            'minLogLevel' => LogLevel::ERROR,
        ], [
            'logLevel' => LogLevel::ERROR,
            'minLogLevel' => LogLevel::CRITICAL,
        ], [
            'logLevel' => LogLevel::CRITICAL,
            'minLogLevel' => LogLevel::ALERT,
        ], [
            'logLevel' => LogLevel::ALERT,
            'minLogLevel' => LogLevel::EMERGENCY,
        ]];
    }

    /**
     * @dataProvider providesLogLevelsThatWillNotTriggerAction
     */
    public function testLogWillNotSendAMessageToSlackIfTheLogLevelIsTooLow($logLevel, $minLogLevel)
    {
        $slackLoggerMock = $this
            ->getMockBuilder(SlackLogger::class)
            ->setConstructorArgs([
                [
                    'webhook_url' => 'https://hooks.slack.com/services/foo/bar/baz',
                    'min_log_level' => $minLogLevel,
                ],
            ])
            ->setMethods(['sendJsonToSlack'])
            ->getMock()
        ;

        $slackLoggerMock
            ->expects($this->never())
            ->method('sendJsonToSlack')
        ;

        $slackLoggerMock->log($logLevel, 'Foo');
    }
}
