# Getting Started

## Requirements

- PHP 7.4+
- cURL extension, if you want to use the Slack logger

## Installation

Install using Composer.  Assuming Composer is installed globally, run the following command at the root of your app.

```sh
composer require danbettles/defence
```

## Basic Usage

The easiest way to get started, using Defence as an intrusion _prevention_ system, is to use the factories to create the building blocks.

```php
use DanBettles\Defence\Factory\DefenceFactory;
use DanBettles\Defence\Factory\EnvelopeFactory;

$envelope = (new EnvelopeFactory())->createDefaultEnvelope();
$defence = (new DefenceFactory())->createDefaultDefenceWithBasicFilters();

$defence->execute($envelope);
```

The envelope factory is used to create a 'default' envelope, which contains a Symfony HTTPFoundation request and a `NullLogger` logger.  `NullLogger` discards all log entries it's given, so you won't hear a peep out of Defence in the above configuration: it'll just quietly get on with filtering requests.

We create an instance of Defence using its own factory.  `createDefaultDefenceWithBasicFilters()` creates an instance of the facade comprising: a filter-chain containing all the basic filters included in the library; and the default handler, which will immediately terminate the script if the request appears to be suspicious.

Defence will be doing very little in this configuration -- although it will reject a surprising number of suspicious requests.  To get the most out of the library you'll need to at least add more filters to the filter chain.  Take a look at [Examples](examples.md) to find out how to take things further.
