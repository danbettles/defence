<?php
declare(strict_types=1);

namespace ThreeStreams\Defence;

use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

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
     * Adds a log, containing some useful information taken from the request, to the logger; by default, adds a warning.
     */
    public function addLog(string $message, string $level = LogLevel::WARNING): self
    {
        $this->getLogger()->log($level, $message, [
            'host_name' => gethostname(),
            'uri' => $this->getRequest()->getUri(),
        ]);

        return $this;
    }
}
