<?php

declare(strict_types=1);

namespace DanBettles\Defence\Handler;

use DanBettles\Defence\Envelope;
use DanBettles\Defence\PhpFunctionsWrapper;
use DanBettles\Defence\Factory\HttpResponseFactory;

class TerminateScriptHandler implements HandlerInterface
{
    /** @var PhpFunctionsWrapper */
    private $phpFunctions;

    /** @var HttpResponseFactory */
    private $httpResponseFactory;

    public function __construct(PhpFunctionsWrapper $phpFunctions, HttpResponseFactory $httpResponseFactory)
    {
        $this
            ->setPhpFunctionsWrapper($phpFunctions)
            ->setHttpResponseFactory($httpResponseFactory)
        ;
    }

    private function setPhpFunctionsWrapper(PhpFunctionsWrapper $phpFunctions): self
    {
        $this->phpFunctions = $phpFunctions;
        return $this;
    }

    public function getPhpFunctionsWrapper(): PhpFunctionsWrapper
    {
        return $this->phpFunctions;
    }

    private function setHttpResponseFactory(HttpResponseFactory $httpResponseFactory): self
    {
        $this->httpResponseFactory = $httpResponseFactory;
        return $this;
    }

    public function getHttpResponseFactory(): HttpResponseFactory
    {
        return $this->httpResponseFactory;
    }

    public function __invoke(Envelope $envelope)
    {
        $this
            ->getHttpResponseFactory()
            ->createForbiddenResponse(
                "We're not going to handle your request because it looks suspicious.  " .
                "Please contact us if we've made a mistake."
            )
            ->prepare($envelope->getRequest())
            ->send()
        ;

        $this
            ->getPhpFunctionsWrapper()
            ->exit()
        ;
    }
}
