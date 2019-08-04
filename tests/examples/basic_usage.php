<?php
//@codingStandardsIgnoreFile

// use Symfony\Component\HttpFoundation\Request;
use ThreeStreams\Defence\Factory\DefenceFactory;
use ThreeStreams\Defence\Factory\EnvelopeFactory;
// use ThreeStreams\Defence\Filter\InvalidNumericIdParameterFilter;
// use ThreeStreams\Defence\Filter\InvalidIso8601DateParameterFilter;
// use ThreeStreams\Defence\Envelope;
// use ThreeStreams\Defence\Logger\Logger;

require_once __DIR__ . '/../../vendor/autoload.php';

$defence = (new DefenceFactory())->createDefault();

//You could add some more filters at this point.
// $defence
//     ->getFilterChain()
//     ->appendFilter(new InvalidNumericIdParameterFilter(['blog_id', 'post_id']))
//     ->appendFilter(new InvalidIso8601DateParameterFilter(['starts_on', 'ends_on']))
// ;

$envelope = (new EnvelopeFactory())->createDefault();

//Alternatively, create an `Envelope` manually so you can use your own logger.
// $envelope = new Envelope(Request::createFromGlobals(), new Logger());

$defence->execute($envelope);
