<?php

declare(strict_types=1);

namespace DanBettles\Defence\Filter;

use DanBettles\Defence\Envelope;
use Symfony\Component\HttpFoundation\Request;

use function array_diff;
use function array_map;
use function implode;
use function is_string;
use function sprintf;
use function strtoupper;

use const false;
use const null;
use const true;

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
    /**
     * @var string[]
     */
    public const VALID_METHODS = [
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

    public function __invoke(Envelope $envelope): bool
    {
        $request = $envelope->getRequest();

        $allMethodOverrides = [
            $request->headers->get('X-HTTP-METHOD-OVERRIDE'),
            $request->request->get('_method'),
            $request->query->get('_method'),
        ];

        $filteredMethodOverrides = [];

        foreach ($allMethodOverrides as $methodOverride) {
            if (null === $methodOverride) {
                continue;
            }

            if (!is_string($methodOverride)) {
                $this->envelopeAddLogEntry($envelope, 'The type of the request-method override is invalid');

                return true;
            }

            $filteredMethodOverrides[] = strtoupper($methodOverride);
        }

        if (!$filteredMethodOverrides) {
            // Nothing at all to worry about
            return false;
        }

        // (Request-method override present)

        $invalidMethodOverrides = array_diff($filteredMethodOverrides, self::VALID_METHODS);

        // Reject the request if any request-method override, found anywhere, is invalid
        if ($invalidMethodOverrides) {
            $this->envelopeAddLogEntry($envelope, sprintf(
                'The request contains invalid request-method overrides: %s',
                implode(
                    ', ',
                    array_map(fn ($invalidMethod) => "`{$invalidMethod}`", $invalidMethodOverrides)
                )
            ));

            return true;
        }

        // (Valid request-method override present)

        // A request-method override should accompany only `POST` requests, so if the method is something else and an
        // override is present then the request is suspicious
        return Request::METHOD_POST !== $request->getRealMethod();
    }
}
