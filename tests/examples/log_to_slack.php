<?php

use Symfony\Component\HttpFoundation\Request;
use ThreeStreams\Defence\Logger\SlackLogger;
use ThreeStreams\Defence\Envelope;
use ThreeStreams\Defence\Factory\DefenceFactory;

require_once __DIR__ . '/../../vendor/autoload.php';

$suspiciousRequest = Request::createFromGlobals();
$suspiciousRequest->headers->remove('User-Agent');

$slackLogger = new SlackLogger([
    'webhook_url' => 'CHANGEME',
]);

$envelope = new Envelope($suspiciousRequest, $slackLogger);

(new DefenceFactory())
    ->createDefault()
    ->execute($envelope)
;
