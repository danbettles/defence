# Examples

## Add Filters to the Chain

The following example expands on the one given earlier, in [Getting Started](getting-started.md), by adding more filters to the default filter chain.

```php
use DanBettles\Defence\Factory\DefenceFactory;
use DanBettles\Defence\Factory\EnvelopeFactory;
use DanBettles\Defence\Factory\FilterFactory;
use DanBettles\Defence\Filter\InvalidSymfonyHttpMethodOverrideFilter;

$envelope = (new EnvelopeFactory())->createDefaultEnvelope();

$defence = (new DefenceFactory())->createDefaultDefenceWithBasicFilters();

$filterFactory = new FilterFactory();

$defence
    ->getFilterChain()

    //All parameters named "starts_on" or "ends_on" must look like ISO 8601 dates or be blank.
    ->appendFilter($filterFactory->createInvalidIso8601DateParameterFilter(['starts_on', 'ends_on']))

    //All parameters named "search_date" must look something like "YYYY-MM-DD" or "DD-MM-YYYY", or be blank.
    ->appendFilter($filterFactory->createInvalidMachineDateParameterFilter(['search_date']))

    //All parameters with names ending with "_id" must contain only digits or be blank.
    ->appendFilter($filterFactory->createInvalidNumericIdParameterFilter('/_id$/'))

    //The HTTP-method override included in the request must be valid.
    ->appendFilter(new InvalidSymfonyHttpMethodOverrideFilter())
;

$defence->execute($envelope);
```

## Send Log Messages to Slack

If you want to keep a closer eye on what Defence is doing then you could use the included Slack logger to send log entries to Slack.  In its default configuration, as below, `SlackLogger` will send _all_ log entries to Slack.

```php
use Symfony\Component\HttpFoundation\Request;
use DanBettles\Defence\Logger\SlackLogger;
use DanBettles\Defence\Envelope;
use DanBettles\Defence\Factory\DefenceFactory;

$envelope = new Envelope(
    Request::createFromGlobals(),
    new SlackLogger('YOUR_APP_WEBHOOK_URL')
);

(new DefenceFactory())
    ->createDefaultDefenceWithBasicFilters()
    ->execute($envelope)
;
```

## Adjust Log Levels

It's likely that some suspicious requests will be more interesting to you than others.  You mightn't want to hear about requests with a suspicious user-agent header, for example, but do want to keep tabs on other filters, to ensure they're not being overzealous.

You can effectively silence a built-in filter simply by making its log-level lower than the minimum log-level of the logger.

In the following example we use the Slack logger to keep an eye on proceedings.

```php
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;
use DanBettles\Defence\Logger\SlackLogger;
use DanBettles\Defence\Envelope;
use DanBettles\Defence\Factory\DefenceFactory;
use DanBettles\Defence\Filter\SuspiciousUserAgentHeaderFilter;
use DanBettles\Defence\Filter\InvalidParameterFilter;

//Send log entries with a log-level of "warning", and above, to Slack.
$slackLogger = new SlackLogger('YOUR_APP_WEBHOOK_URL', [
    'min_log_level' => LogLevel::WARNING,
]);

$envelope = new Envelope(Request::createFromGlobals(), $slackLogger);

//Creates an instance of Defence containing an empty filter chain.
$defence = (new DefenceFactory())->createDefaultDefence();

$defence
    ->getFilterChain()

    //Log entries created by this filter won't make it to Slack...
    ->appendFilter(new SuspiciousUserAgentHeaderFilter(['log_level' => LogLevel::NOTICE]))

    //...Whereas log entries created by this one will.
    ->appendFilter(new InvalidParameterFilter(['q'], '/^[a-z]*$/i', ['log_level' => LogLevel::WARNING]))
;

$defence->execute($envelope);
```

By default, built-in filters have a log-level of `"warning"`, and, by default, `SlackLogger` will send _all_ log entries.
