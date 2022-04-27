<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use DanBettles\Defence\Logger\SlackLogger;
use DanBettles\Defence\Envelope;
use DanBettles\Defence\Factory\DefenceFactory;

require_once __DIR__ . '/../../vendor/autoload.php';

$suspiciousRequest = Request::createFromGlobals();
$suspiciousRequest->headers->remove('User-Agent');

$slackLogger = new SlackLogger('YOUR_APP_WEBHOOK_URL');

$envelope = new Envelope($suspiciousRequest, $slackLogger);

(new DefenceFactory())
    ->createDefaultDefenceWithBasicFilters()
    ->execute($envelope)
;
