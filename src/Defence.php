<?php

declare(strict_types=1);

namespace DanBettles\Defence;

use DanBettles\Gestalt\SimpleFilterChain;
use DanBettles\Defence\Handler\HandlerInterface;

use const true;

class Defence
{
    /** @var SimpleFilterChain */
    private $filterChain;

    /** @var HandlerInterface */
    private $handler;

    public function __construct(SimpleFilterChain $filterChain, HandlerInterface $handler)
    {
        $this
            ->setFilterChain($filterChain)
            ->setHandler($handler)
        ;
    }

    private function setFilterChain(SimpleFilterChain $filterChain): self
    {
        $this->filterChain = $filterChain;

        return $this;
    }

    public function getFilterChain(): SimpleFilterChain
    {
        return $this->filterChain;
    }

    private function setHandler(HandlerInterface $handler): self
    {
        $this->handler = $handler;

        return $this;
    }

    public function getHandler(): HandlerInterface
    {
        return $this->handler;
    }

    /**
     * Executes the filter chain and, if the request was deemed suspicious, triggers the handler.
     */
    public function execute(Envelope $envelope): void
    {
        $requestIsSuspicious = $this->getFilterChain()->execute($envelope, true);

        if ($requestIsSuspicious) {
            $this->getHandler()($envelope);
        }
    }
}
