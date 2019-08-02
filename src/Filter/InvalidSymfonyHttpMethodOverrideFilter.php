<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Filter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use ThreeStreams\Defence\Envelope;

/**
 * Using the Symfony request object it is possible to simulate exotic HTTP methods (e.g. `PUT`) by making a `POST`
 * request and specifying the intended method in a special parameter or in a special header.
 *
 * We've seen requests that have tried to exploit this feature.  It's an attack vector that's easy to detect, so we
 * provided this filter, which will return `true` if the instructed request method looks dodgy.
 */
class InvalidSymfonyHttpMethodOverrideFilter implements FilterInterface
{
    /** @var string[] */
    private $validMethods = [
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
        try {
            $filteredMethod = $envelope->getRequest()->getMethod();
        } catch (SuspiciousOperationException $e) {
            return true;
        }

        //(In theory) if we got this far then Symfony doesn't think the request method is suspicious.  Full stop.

        //However, `Request` currently allows certain 'invalid' methods to pass through.  Something like `"__construct"`
        //will be rejected but `"foo"` will become `"FOO"`.  We must, therefore, do one last check.
        if (!\in_array($filteredMethod, $this->validMethods, true)) {
            $envelope->addLog("The request contains an invalid Symfony HTTP method override (`{$filteredMethod}`).");
            return true;
        }

        return false;
    }
}
