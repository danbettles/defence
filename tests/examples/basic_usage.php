<?php

use ThreeStreams\Defence\Factory\DefenceFactory;
use ThreeStreams\Defence\Factory\EnvelopeFactory;

//@codingStandardsIgnoreStart
// use ThreeStreams\Defence\Filter\InvalidNumericIdParameterFilter;
// use ThreeStreams\Defence\Filter\InvalidIso8601DateParameterFilter;
//@codingStandardsIgnoreEnd

require_once __DIR__ . '/../../vendor/autoload.php';

$envelope = (new EnvelopeFactory())->createDefault();

$defence = (new DefenceFactory())->createDefault();

//You could add some more filters at this point.
// $defence
//     ->getFilterChain()
//     ->appendFilter(new InvalidNumericIdParameterFilter(['blog_id', 'post_id']))
//     ->appendFilter(new InvalidIso8601DateParameterFilter(['starts_on', 'ends_on']))
// ;

$defence->execute($envelope);
