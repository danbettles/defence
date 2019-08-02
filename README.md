# Defence

A clean, un-opinionated intrusion detection/protection system for PHP apps.

**N.B. _Defence_ does not eliminate the need to filter input.**

_Defence_ could be used simply to _detect_ suspicious requests but, with the included handler, will stop the script dead in its tracks.  _Defence_ is used principally to (1) prevent a suspicious-looking request getting any further into your code and potentially exploiting vulnerabilities, and (2) avoid wasting further system resources.

A small selection of basic filters are provided but _Defence_ is more a framework.

_Defence_ grew out of a class written for a legacy app that was frequently hounded by script kiddies.  We kept seeing the same easy-to-detect attacks and wanted to quickly defend against them.  That legacy app is now being rebuilt on _Symfony_ framework but _Defence_ still sits up front and filters requests.

## Architecture

At the heart of _Defence_ is a simple filter-chain comprising a number of callable objects, 'filters'.  The current request, in the form of a _Symfony_ HttpFoundation `Request` object, along with a PSR-3-compatible logger, is passed to each filter in turn.  If a filter returns `true` then a 'handler' is immediately executed and processing stops.

### Envelope

The current request and the logger are packaged in an `Envelope` object.  It's this same `Envelope` object that's passed into each filter and then, if the request is considered suspicious, the handler.

### Filters

A filter is an instance of a class that implements the filter interface.  In its most basic form, a filter has only to say whether it considers the current request suspicious or not.

### Handler

A handler is an instance of a class that implements the handler interface.  A handler can do anything.  The default handler sends an HTTP "Forbidden" response and then terminates the script.

## Installation

Install using _Composer_.  Assuming _Composer_ is installed globally, run the following command at the root of your app.

```sh
composer require threestreams/defence
```

## Usage

The easiest way to get started is to use the factory to create a preconfigured instance of the facade, `Defence`.  The object will be preloaded with all the basic filters included in the library, and will use the default handler, which will immediately terminate the script if the request appears to be suspicious.

```php
// use Symfony\Component\HttpFoundation\Request;
use ThreeStreams\Defence\Factory\DefenceFactory;
use ThreeStreams\Defence\Factory\EnvelopeFactory;
// use ThreeStreams\Defence\Filter\InvalidNumericIdParameterFilter;
// use ThreeStreams\Defence\Filter\InvalidIso8601DateParameterFilter;
// use ThreeStreams\Defence\Envelope;
// use ThreeStreams\Defence\Logger;

//...

$defence = (new DefenceFactory())->createDefault();

//You could add some more filters at this point.
// $defence
//     ->getFilterChain()
//     ->appendFilter(new InvalidNumericIdParameterFilter(['blog_id', 'post_id']))
//     ->appendFilter(new InvalidIso8601DateParameterFilter(['starts_on', 'ends_on']))
// ;

$envelope = (new EnvelopeFactory())->createDefault();

//Alternatively, create an `Envelope` manually so you can use your own logger or configure the request.
// $envelope = new Envelope(Request::createFromGlobals(), new Logger());

$defence->execute($envelope);
```
