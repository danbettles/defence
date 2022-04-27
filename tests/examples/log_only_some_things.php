<?php

declare(strict_types=1);

use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;
use DanBettles\Defence\Logger\SlackLogger;
use DanBettles\Defence\Envelope;
use DanBettles\Defence\Factory\DefenceFactory;
use DanBettles\Defence\Filter\SuspiciousUserAgentHeaderFilter;
use DanBettles\Defence\Filter\InvalidParameterFilter;

require_once __DIR__ . '/../../vendor/autoload.php';

//Note the minimum log level.
$slackLogger = new SlackLogger('YOUR_APP_WEBHOOK_URL', [
    'min_log_level' => LogLevel::WARNING,
]);

$envelope = new Envelope(Request::createFromGlobals(), $slackLogger);

$defence = (new DefenceFactory())->createDefaultDefence();

$defence
    ->getFilterChain()
    //Log entries created by this filter won't make it to Slack...
    ->appendFilter(new SuspiciousUserAgentHeaderFilter(['log_level' => LogLevel::NOTICE]))
    //...Whereas log entries created by this one will.
    ->appendFilter(new InvalidParameterFilter(['q'], '/^[a-z]*$/i', ['log_level' => LogLevel::WARNING]))
;

$defence->execute($envelope);
