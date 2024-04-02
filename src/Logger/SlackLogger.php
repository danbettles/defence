<?php

declare(strict_types=1);

namespace DanBettles\Defence\Logger;

use CurlHandle;
use Exception;
use InvalidArgumentException;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function array_replace;
use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function filter_var;
use function is_scalar;
use function json_encode;
use function print_r;
use function sprintf;
use function ucfirst;

use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_URL;
use const FILTER_VALIDATE_URL;
use const false;
use const true;

/**
 * A logger that sends log entries to Slack.
 *
 * A message will be sent to Slack only if its log-level meets or exceeds the minimum log-level of the logger.  The
 * default minimum log-level of the logger is `"debug"` to ensure that, out of the box, the logger will send all
 * messages to Slack.
 *
 * @phpstan-import-type Context from NullLogger
 *
 * @phpstan-type LoggerOptions array<string,string>
 */
class SlackLogger extends AbstractLogger
{
    /**
     * @var array<string,int>
     */
    private const LOG_LEVEL_PRIORITY = [
        LogLevel::EMERGENCY => 8,
        LogLevel::ALERT => 7,
        LogLevel::CRITICAL => 6,
        LogLevel::ERROR => 5,
        LogLevel::WARNING => 4,
        LogLevel::NOTICE => 3,
        LogLevel::INFO => 2,
        LogLevel::DEBUG => 1,
    ];

    /**
     * @var array<string,string>
     */
    private const LOG_LEVEL_EMOJI = [
        LogLevel::EMERGENCY => ':bangbang:',
        LogLevel::ALERT => ':bangbang:',
        LogLevel::CRITICAL => ':bangbang:',
        LogLevel::ERROR => ':bangbang:',
        LogLevel::WARNING => ':warning:',
        LogLevel::NOTICE => ':information_source:',
        LogLevel::INFO => ':information_source:',
        LogLevel::DEBUG => ':information_source:',
    ];

    /**
     * @var string
     */
    private $webhookUrl;

    /**
     * @phpstan-var LoggerOptions
     */
    private $options;

    /**
     * @phpstan-param LoggerOptions $options
     */
    public function __construct(string $webhookUrl, array $options = [])
    {
        $this
            ->setWebhookUrl($webhookUrl)
            ->setOptions(array_replace([
                'min_log_level' => LogLevel::DEBUG,
            ], $options))
        ;
    }

    /**
     * @throws InvalidArgumentException If the webhook URL is not a valid URL.
     */
    private function setWebhookUrl(string $url): self
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('The webhook URL is not a valid URL.');
        }

        $this->webhookUrl = $url;

        return $this;
    }

    public function getWebhookUrl(): string
    {
        return $this->webhookUrl;
    }

    /**
     * @phpstan-param LoggerOptions $options
     */
    private function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @phpstan-return LoggerOptions
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @throws Exception If it failed to initialize a new cURL session.
     * @throws Exception If `curl_exec()` did not return the result.
     * @throws Exception If it failed to send the JSON to Slack.
     */
    protected function sendJsonToSlack(string $json): string
    {
        /** @var CurlHandle|false */
        $curl = curl_init();

        if (false === $curl) {
            throw new Exception('Failed to initialize a new cURL session.');
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, Request::METHOD_POST);
        curl_setopt($curl, CURLOPT_URL, $this->getWebhookUrl());
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);

        $result = curl_exec($curl);

        if (true === $result) {
            throw new Exception("`curl_exec()` did not return the result.");
        }

        $httpResponseCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if (false === $result || Response::HTTP_OK !== $httpResponseCode) {
            throw new Exception('Failed to send the JSON to Slack.');
        }

        return $result;
    }

    /**
     * Returns `true` if the specified log-level is sufficient to trigger the logger action, or `false` otherwise.
     */
    private function logLevelIsSufficient(string $logLevel): bool
    {
        $minLogLevel = $this->getOptions()['min_log_level'];

        return self::LOG_LEVEL_PRIORITY[$logLevel] >= self::LOG_LEVEL_PRIORITY[$minLogLevel];
    }

    /**
     * @override
     * @param string $level
     * @phpstan-param Context $context
     */
    public function log(
        $level,
        string|Stringable $message,
        array $context = []
    ): void {
        if (!$this->logLevelIsSufficient($level)) {
            return;
        }

        $logLevelEmoji = self::LOG_LEVEL_EMOJI[$level];

        $contextElements = [];

        foreach ($context as $name => $value) {
            //@codingStandardsIgnoreStart
            $valueFormatted = is_scalar($value)
                ? $value
                : '```' . /** @scrutinizer ignore-type */ print_r($value, true) . '```'
            ;
            //@codingStandardsIgnoreEnd

            $contextElements[] = [
                'type' => 'mrkdwn',
                'text' => "{$name}: {$valueFormatted}",
            ];
        }

        /** @var string */
        $json = json_encode([
            'text' => "{$logLevelEmoji} Defence handled suspicious request",
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
                        'text' => sprintf("*%s %s: %s*", $logLevelEmoji, ucfirst($level), $message),
                    ],
                ],
                [
                    'type' => 'context',
                    'elements' => $contextElements,
                ],
            ],
        ]);

        $this->sendJsonToSlack($json);
    }
}
