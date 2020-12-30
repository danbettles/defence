<?php

declare(strict_types=1);

namespace ThreeStreams\Defence\Filter;

use Symfony\Component\HttpFoundation\Request;
use ThreeStreams\Defence\Envelope;

/**
 * Using the Symfony request object it is possible to simulate exotic HTTP methods (e.g. `PUT`) by making a `POST`
 * request and specifying the intended method in a special parameter or in a special header.
 *
 * We've seen requests that have tried to exploit this feature.  It's an attack vector that's easy to detect, so we
 * provided this simple filter, which will return `true` if the request-method override looks at all dodgy.
 *
 * The approach is very simple: if a request looks even vaguely dodgy then the filter will reject it, even if Symfony
 * wouldn't touch it anyway.
 */
class InvalidSymfonyHttpMethodOverrideFilter extends AbstractFilter
{
    /** @var string[] */
    private const VALID_METHODS = [
        Request::METHOD_HEAD,
        Request::METHOD_GET,
        Request::METHOD_POST,
        Request::METHOD_PUT,
        Request::METHOD_PATCH,
        Request::METHOD_DELETE,
        Request::METHOD_PURGE,
        Request::METHOD_OPTIONS,
        Request::METHOD_TRACE,
        Request::METHOD_CONNECT,
    ];

    /**
     * {@inheritDoc}
     */
    public function __invoke(Envelope $envelope): bool
    {
        $request = $envelope->getRequest();

        //We're interested in only `POST` requests.
        if (Request::METHOD_POST !== $request->getRealMethod()) {
            return false;
        }

        //Pull out all override methods included in the request.
        $overrideMethods = \array_map('\strtoupper', \array_filter([
            $request->headers->get('X-HTTP-METHOD-OVERRIDE'),
            $request->request->get('_method'),
            $request->query->get('_method'),
        ]));

        $invalidMethods = \array_diff($overrideMethods, self::VALID_METHODS);

        //Reject the request if any override method, found anywhere, is invalid.
        if (!empty($invalidMethods)) {
            $logMessage = \sprintf(
                'The request contains invalid override methods: %s',
                \implode(', ', \array_map(function ($invalidMethod) {
                    return "`{$invalidMethod}`";
                }, $invalidMethods))
            );

            $this->envelopeAddLogEntry($envelope, $logMessage);

            return true;
        }

        return false;
    }
}
