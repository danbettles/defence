<?php

declare(strict_types=1);

namespace DanBettles\Defence\Filter;

use DanBettles\Defence\Envelope;
use InvalidArgumentException;

use function is_array;
use function is_string;
use function preg_match;

use const false;
use const true;

/**
 * Rejects requests containing the specified user-agent string(s).
 *
 * Apparently, some malicious users don't know/think to change the user-agent string in their scripts, so this filter
 * can be used to quickly reject probing/harmful requests--requests sent via unusual user agents.  Commercial
 * screen-scraping apps identify themselves, and experienced programmers know to use a sensible user-agent string when
 * working with tools like cURL.
 */
class BannedUserAgentHeaderFilter extends AbstractFilter
{
    /** @var array|string */
    private $selector;

    /**
     * @param array|string $selector  One/more regular expressions.
     * @param array $options
     */
    public function __construct($selector, array $options = [])
    {
        parent::__construct($options);

        $this->setSelector($selector);
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(Envelope $envelope): bool
    {
        $uaString = $envelope
            ->getRequest()
            ->headers
            ->get('User-Agent')
        ;

        foreach ((array) $this->getSelector() as $selector) {
            if (preg_match($selector, $uaString)) {
                $this->envelopeAddLogEntry($envelope, 'The request was made via a banned user agent.');
                return true;
            }
        }

        return false;
    }

    /**
     * @param array|string $selector  One/more regular expressions.
     * @throws InvalidArgumentException If the selector is invalid.
     */
    private function setSelector($selector): self
    {
        if (
            empty($selector)
            || (!is_array($selector) && !is_string($selector))
        ) {
            throw new InvalidArgumentException('The selector is invalid.');
        }

        $this->selector = $selector;

        return $this;
    }

    /**
     * @return array|string
     */
    public function getSelector()
    {
        return $this->selector;
    }
}
