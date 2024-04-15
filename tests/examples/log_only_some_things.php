<?php

declare(strict_types=1);

use DanBettles\Defence\Envelope;
use DanBettles\Defence\Factory\DefenceFactory;
use DanBettles\Defence\Factory\FilterFactory;
use DanBettles\Defence\Filter\InvalidParameterFilter;
use DanBettles\Defence\Logger\SlackLogger;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../../vendor/autoload.php';

// Send log entries with a log-level of "warning", and above, to Slack
$slackLogger = new SlackLogger('YOUR_APP_WEBHOOK_URL', [
    'min_log_level' => LogLevel::WARNING,
]);

$envelope = new Envelope(Request::createFromGlobals(), $slackLogger);
$filterFactory = new FilterFactory();

// Creates an instance of Defence containing an empty filter-chain
$defence = (new DefenceFactory())->createDefaultDefence();

$defence
    ->getFilterChain()

    // Log entries created by this filter won't make it to Slack...
    ->appendFilter($filterFactory->createSuspiciousUserAgentHeaderFilter(['log_level' => LogLevel::NOTICE]))

    // ...Whereas log entries created by this one will
    ->appendFilter(new InvalidParameterFilter(['q'], '/^[a-z]*$/i', ['log_level' => LogLevel::WARNING]))
;

$defence->execute($envelope);
