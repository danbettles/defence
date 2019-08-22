# Architecture

At the heart of Defence is a simple filter-chain comprising a number of callable objects, 'filters'.  The current request, in the form of a Symfony HttpFoundation `Request` object, along with a PSR-3 logger, is passed to each filter in turn.  If a filter returns `true` then a 'handler' is immediately executed and processing stops.

## Envelope

The current request and the logger are packaged in an `Envelope` object.  It's this same `Envelope` object that's passed into each filter and then, if the request is considered suspicious, the handler.

## Filters

A filter is an instance of a class that implements the filter interface.  In its most basic form, a filter has only to say whether it considers the current request suspicious or not.

Some of the included filters, such as `InvalidParameterFilter`, are configurable.  To make it easier to get up and running, a number of factory methods are included in `FilterFactory` that build useful variants of configurable filters.

## Handler

A handler is an instance of a class that implements the handler interface.  A handler can do anything.  As an example, the default handler sends an HTTP "Forbidden" response and then terminates the script.
