<?php declare(strict_types=1);

namespace ThreeStreams\Defence;

use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * An `Envelope` object is passed down the filter chain and then, if the request was deemed suspicious, into the handler.
 */
class Envelope implements LoggerAwareInterface
{
    /** @var Request */
    private $request;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Request $request, LoggerInterface $logger)
    {
        $this->setRequest($request);
        $this->setLogger($logger);
    }

    private function setRequest(Request $request): self
    {
        $this->request = $request;
        return $this;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * {@inheritDoc}
     * @see LoggerAwareInterface::setLogger()
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Adds a log entry, containing some useful information taken from the request, to the logger.
     */
    public function addLogEntry(string $level, string $message): void
    {
        $context = [
            'host_name' => gethostname(),
            'uri' => $this->getRequest()->getUri(),
        ];

        if ($this->getRequest()->headers->has('User-Agent')) {
            $context['user_agent'] = $this->getRequest()->headers->get('User-Agent');
        }

        if ($this->getRequest()->headers->has('referer')) {
            $context['referer'] = $this->getRequest()->headers->get('referer');
        }

        $this->getLogger()->log($level, $message, $context);
    }
}
