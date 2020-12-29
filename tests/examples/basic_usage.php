<?php

declare(strict_types=1);

use ThreeStreams\Defence\Factory\DefenceFactory;
use ThreeStreams\Defence\Factory\EnvelopeFactory;

//@codingStandardsIgnoreStart
// use ThreeStreams\Defence\Factory\FilterFactory;
// use ThreeStreams\Defence\Filter\InvalidSymfonyHttpMethodOverrideFilter;
//@codingStandardsIgnoreEnd

require_once __DIR__ . '/../../vendor/autoload.php';

$envelope = (new EnvelopeFactory())->createDefaultEnvelope();

$defence = (new DefenceFactory())->createDefaultDefenceWithBasicFilters();

//You could add some more filters at this point:

// $filterFactory = new FilterFactory();

// $defence
//     ->getFilterChain()
//     ->appendFilter($filterFactory->createInvalidIso8601DateParameterFilter(['starts_on', 'ends_on']))
//     ->appendFilter($filterFactory->createInvalidMachineDateParameterFilter(['search_date']))
//     ->appendFilter($filterFactory->createInvalidNumericIdParameterFilter('/_id$/'))
//     ->appendFilter(new InvalidSymfonyHttpMethodOverrideFilter())
// ;

$defence->execute($envelope);
