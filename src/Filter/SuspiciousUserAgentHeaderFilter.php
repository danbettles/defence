<?php

declare(strict_types=1);

namespace DanBettles\Defence\Filter;

use DanBettles\Defence\Envelope;

use function trim;

use const false;
use const null;
use const true;

/**
 * This filter can help to safely eliminate a swathe of suspicious requests.
 *
 * It appears that all friendly user-agents, including browsers, bots, and command-line tools, are happy to identify
 * themselves -- indeed many now include their origin in their UA string.  We've seen plenty malicious requests
 * with a blank, or absent, user-agent header but never a benign request with no UA string.
 */
class SuspiciousUserAgentHeaderFilter extends AbstractFilter
{
    public function __invoke(Envelope $envelope): bool
    {
        $uaString = $envelope
            ->getRequest()
            ->headers
            ->get('User-Agent')
        ;

        if (null === $uaString) {
            $this->envelopeAddLogEntry($envelope, 'The request has no UA string.');

            return true;
        }

        $uaStringTrimmed = trim($uaString);

        if ('' === $uaStringTrimmed || '-' === $uaStringTrimmed) {
            $this->envelopeAddLogEntry($envelope, 'The request has a suspicious UA string.');

            return true;
        }

        return false;
    }
}
