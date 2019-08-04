<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Logger;

use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;
use InvalidArgumentException;

/**
 * A simple logger that sends all log messages to Slack.
 */
class SlackLogger extends AbstractLogger
{
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
     */
    private function setConfig(array $config): self
    {
        if (!array_key_exists('webhook_url', $config)) {
            throw new InvalidArgumentException('`webhook_url` is missing.');
        }

        $this->config = $config;

        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @throws Exception If it failed to send the JSON to Slack.
     */
    protected function sendJsonToSlack(string $json): string
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($curl, CURLOPT_URL, $this->getConfig()['webhook_url']);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, Request::METHOD_POST);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);

        $result = curl_exec($curl);
        curl_close($curl);

        if (false === $result) {
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
        //@codingStandardsIgnoreStart
        $contextFormatted = print_r($context, true);
        //@codingStandardsIgnoreEnd

        $slackMessageTextParts = [
            'Level' => $level,
            'Message' => $message,
            'Context' => empty($context)
                ? null
                : "```{$contextFormatted}```",
        ];

        $slackMessageText = '';

        foreach ($slackMessageTextParts as $name => $value) {
            if (null === $value) {
                continue;
            }

            $slackMessageText .= "*{$name}*\n{$value}\n";
        }

        $slackMessageText = rtrim($slackMessageText);

        $this->sendJsonToSlack(json_encode([
            'text' => $slackMessageText,
        ]));
    }
}
