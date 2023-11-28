<?php

declare(strict_types=1);

namespace DanBettles\Defence\Filter;

use DanBettles\Defence\Envelope;
use InvalidArgumentException;

use function is_array;
use function is_string;
use function preg_match;

use const false;
use const null;
use const true;

/**
 * Rejects requests containing the specified user-agent string(s).
 *
 * Apparently, some malicious users don't know/think to change the user-agent string in their scripts, so this filter
 * can be used to quickly reject probing/harmful requests--requests sent via unusual user agents.  Commercial
 * screen-scraping apps identify themselves, and experienced programmers know to use a sensible user-agent string when
 * working with tools like cURL.
 *
 * @phpstan-import-type IncomingFilterOptions from \DanBettles\Defence\Filter\AbstractFilter
 *
 * @phpstan-type Selector string|string[]
 */
class BannedUserAgentHeaderFilter extends AbstractFilter
{
    /**
     * @phpstan-var Selector
     */
    private $selector;

    /**
     * @phpstan-param Selector $selector  One/more regular expressions.
     * @phpstan-param IncomingFilterOptions $options
     */
    public function __construct($selector, array $options = [])
    {
        parent::__construct($options);

        $this->setSelector($selector);
    }

    public function __invoke(Envelope $envelope): bool
    {
        $request = $envelope->getRequest();
        /** @var string|null Contrary to what Scrutinizer thinks */
        $uaString = /** @scrutinizer ignore-type */ $request->headers->get('User-Agent');

        if (null === $uaString) {
            // Since there's no user-agent header, we have nothing to check against our blacklist.  *In this context*,
            // then, we have to treat the request as not suspicious -- even though it *is* suspicious that there's no
            // user-agent header.
            return false;
        }

        foreach ((array) $this->getSelector() as $selector) {
            if (preg_match($selector, $uaString)) {
                $this->envelopeAddLogEntry($envelope, 'The request was made via a banned user agent.');

                return true;
            }
        }

        return false;
    }

    /**
     * @phpstan-param Selector $selector  One/more regular expressions.
     * @throws InvalidArgumentException If the selector is invalid
     */
    private function setSelector($selector): self
    {
        if (
            empty($selector)
            /** @phpstan-ignore-next-line */
            || (!is_array($selector) && !is_string($selector))
        ) {
            throw new InvalidArgumentException('The selector is invalid');
        }

        $this->selector = $selector;

        return $this;
    }

    /**
     * @phpstan-return Selector
     */
    public function getSelector()
    {
        return $this->selector;
    }
}
