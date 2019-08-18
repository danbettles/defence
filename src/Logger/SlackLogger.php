<?php declare(strict_types=1);

namespace ThreeStreams\Defence\Logger;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use InvalidArgumentException;
use Exception;

/**
 * A simple logger that sends all log messages to Slack.
 */
class SlackLogger extends AbstractLogger
{
    /** @var string[] */
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

    /** @var array */
    private $config;

    /**
     * You must specify your Slack app's webhook URL in the `webhook_url` element of the config array.
     */
    public function __construct(array $config)
    {
        $this->setConfig($config);
    }

    /**
     * @throws InvalidArgumentException If `webhook_url` is missing.
     * @throws InvalidArgumentException If the webhook URL is not a valid URL.
     */
    private function setConfig(array $config): self
    {
        if (!\array_key_exists('webhook_url', $config)) {
            throw new InvalidArgumentException('`webhook_url` is missing.');
        }

        if (!\filter_var($config['webhook_url'], FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('The webhook URL is not a valid URL.');
        }

        $this->config = $config;

        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @throws Exception If it failed to initialize a new cURL session.
     * @throws Exception If `curl_exec()` did not return the result.
     * @throws Exception If it failed to send the JSON to Slack.
     */
    protected function sendJsonToSlack(string $json): string
    {
        $curl = \curl_init();

        if (false === $curl) {
            throw new Exception('Failed to initialize a new cURL session.');
        }

        \curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        \curl_setopt($curl, CURLOPT_CUSTOMREQUEST, Request::METHOD_POST);
        \curl_setopt($curl, CURLOPT_URL, $this->getConfig()['webhook_url']);
        \curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        \curl_setopt($curl, CURLOPT_POSTFIELDS, $json);

        $result = \curl_exec($curl);

        if (true === $result) {
            throw new Exception("`curl_exec()` did not return the result.");
        }

        $httpResponseCode = \curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        \curl_close($curl);

        if (false === $result || Response::HTTP_OK !== $httpResponseCode) {
            throw new Exception('Failed to send the JSON to Slack.');
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     * @see LoggerInterface::log()
     */
    public function log($level, $message, array $context = array())
    {
        $logLevelEmoji = self::LOG_LEVEL_EMOJI[$level];

        $contextElements = [];

        foreach ($context as $name => $value) {
            //@codingStandardsIgnoreStart
            $valueFormatted = \is_scalar($value)
                ? $value
                : \print_r($value, true)
            ;
            //@codingStandardsIgnoreEnd

            $contextElements[] = [
                'type' => 'mrkdwn',
                'text' => "{$name}: {$valueFormatted}",
            ];
        }

        $this->sendJsonToSlack(\json_encode([
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
                        'text' => \sprintf("*%s %s: %s*", $logLevelEmoji, \ucfirst($level), $message),
                    ],
                ],
                [
                    'type' => 'context',
                    'elements' => $contextElements,
                ],
            ],
        ]));
    }
}
