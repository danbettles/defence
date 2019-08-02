<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Filter;

use ThreeStreams\Defence\Envelope;

/**
 * This filter will help safely eliminate a swathe of suspicious requests.
 *
 * It appears that all friendly user-agents, including browsers, bots, and command-line tools, are happy to identify
 * themselves -- indeed many now include their origin in their UA string.  We've seen plenty malicious requests
 * with a blank, or absent, user-agent header but never a benign request with no UA string.
 */
class BlankUserAgentHeaderFilter implements FilterInterface
{
    /**
     * {@inheritDoc}
     */
    public function __invoke(Envelope $envelope): bool
    {
        //Blank or absent.
        if ('' === \trim($envelope->getRequest()->headers->get('User-Agent', ''))) {
            $envelope->addLog('The request has no UA string.');
            return true;
        }

        return false;
    }
}
