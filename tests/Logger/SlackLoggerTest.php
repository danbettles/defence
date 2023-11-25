<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Logger;

use DanBettles\Defence\Logger\SlackLogger;
use DanBettles\Defence\Tests\AbstractTestCase;
use Exception;
use InvalidArgumentException;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

use function json_encode;

/**
 * @phpstan-import-type Context from SlackLogger
 */
class SlackLoggerTest extends AbstractTestCase
{
    public function testIsAPsrAbstractlogger(): void
    {
        $this->assertSubclassOf(AbstractLogger::class, SlackLogger::class);
    }

    public function testConstructor(): void
    {
        $logger = new SlackLogger('https://hooks.slack.com/services/foo/bar/baz', ['foo' => 'bar']);

        $this->assertSame('https://hooks.slack.com/services/foo/bar/baz', $logger->getWebhookUrl());

        $this->assertSame([
            'min_log_level' => LogLevel::DEBUG,
            'foo' => 'bar',
        ], $logger->getOptions());
    }

    /** @return array<mixed[]> */
    public function providesInvalidWebhookUrls(): array
    {
        return [[
            '',
        ], [
            'foo'
        ]];
    }

    /** @dataProvider providesInvalidWebhookUrls */
    public function testConstructorThrowsAnExceptionIfTheWebhookUrlIsNotAValidUrl(string $invalidWebhookUrl): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The webhook URL is not a valid URL.');

        new SlackLogger($invalidWebhookUrl, []);
    }

    public function testOptionsAreOptional(): void
    {
        $logger = new SlackLogger('https://hooks.slack.com/services/foo/bar/baz');

        $this->assertSame([
            'min_log_level' => LogLevel::DEBUG,
        ], $logger->getOptions());
    }

    /** @return array<mixed[]> */
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
     * @phpstan-param Context $context
     */
    public function testAllLogMethodsCallLog(
        string $level,
        string $message,
        array $context,
        string $loggerMethodName
    ): void {
        $slackLoggerMock = $this
            ->getMockBuilder(SlackLogger::class)
            ->setConstructorArgs([
                'https://hooks.slack.com/services/foo/bar/baz',
            ])
            ->onlyMethods(['log'])
            ->getMock()
        ;

        $slackLoggerMock
            ->expects($this->once())
            ->method('log')
            ->with($level, $message, $context)
        ;

        $slackLoggerMock->{$loggerMethodName}($message, $context);
    }

    public function testLogWillSendAMessageToSlack(): void
    {
        $level = LogLevel::ERROR;
        $message = 'Runtime error that does not require immediate action.';
        $context = ['xyzzy' => 'thud'];

        $expectedJson = json_encode([
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
            ->setConstructorArgs([
                'https://hooks.slack.com/services/foo/bar/baz',
            ])
            ->onlyMethods(['sendJsonToSlack'])
            ->getMock()
        ;

        $slackLoggerMock
            ->expects($this->once())
            ->method('sendJsonToSlack')
            ->with($expectedJson)
        ;

        /** @var SlackLogger $slackLoggerMock */

        $slackLoggerMock->log($level, $message, $context);
    }

    public function testLogThrowsAnExceptionIfItFailedToCommunicateWithSlack(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to send the JSON to Slack.');

        $logger = new SlackLogger('http://localhost');
        $logger->log(LogLevel::ERROR, 'bar');
    }

    /** @return array<mixed[]> */
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

    /** @dataProvider providesLogLevelsThatWillTriggerAction */
    public function testLogWillSendAMessageToSlackIfTheLogLevelIsHighEnough(
        string $logLevel,
        string $minLogLevel
    ): void {
        $slackLoggerMock = $this
            ->getMockBuilder(SlackLogger::class)
            ->setConstructorArgs([
                'https://hooks.slack.com/services/foo/bar/baz',
                ['min_log_level' => $minLogLevel],
            ])
            ->onlyMethods(['sendJsonToSlack'])
            ->getMock()
        ;

        $slackLoggerMock
            ->expects($this->once())
            ->method('sendJsonToSlack')
        ;

        /** @var SlackLogger $slackLoggerMock */

        $slackLoggerMock->log($logLevel, 'Foo');
    }

    /** @return array<mixed[]> */
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

    /** @dataProvider providesLogLevelsThatWillNotTriggerAction */
    public function testLogWillNotSendAMessageToSlackIfTheLogLevelIsTooLow(
        string $logLevel,
        string $minLogLevel
    ): void {
        $slackLoggerMock = $this
            ->getMockBuilder(SlackLogger::class)
            ->setConstructorArgs([
                'https://hooks.slack.com/services/foo/bar/baz',
                ['min_log_level' => $minLogLevel],
            ])
            ->onlyMethods(['sendJsonToSlack'])
            ->getMock()
        ;

        $slackLoggerMock
            ->expects($this->never())
            ->method('sendJsonToSlack')
        ;

        /** @var SlackLogger $slackLoggerMock */

        $slackLoggerMock->log($logLevel, 'Foo');
    }
}
