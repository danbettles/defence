<?php

declare(strict_types=1);

namespace DanBettles\Defence\Factory;

use DanBettles\Defence\Defence;
use DanBettles\Defence\Handler\TerminateScriptHandler;
use DanBettles\Defence\PhpFunctionsWrapper;
use DanBettles\Gestalt\SimpleFilterChain;

class DefenceFactory
{
    private function createTerminateScriptHandler(): TerminateScriptHandler
    {
        return new TerminateScriptHandler(new PhpFunctionsWrapper(), new HttpResponseFactory());
    }

    /**
     * Creates an instance of `Defence` containing no filters, and a handler that will immediately send a "Forbidden"
     * response and terminate the script
     */
    public function createDefaultDefence(): Defence
    {
        return new Defence(new SimpleFilterChain(), $this->createTerminateScriptHandler());
    }

    /**
     * Creates an instance of `Defence` in the default configuration and then adds filters that require no set-up and
     * can be safely run in all cases
     */
    public function createDefaultDefenceWithBasicFilters(): Defence
    {
        $filterFactory = new FilterFactory();

        $defence = $this->createDefaultDefence();

        $defence
            ->getFilterChain()
            ->appendFilter($filterFactory->createSuspiciousUserAgentHeaderFilter())
        ;

        return $defence;
    }
}
