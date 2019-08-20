<?php declare(strict_types=1);

namespace ThreeStreams\Defence\Factory;

use ThreeStreams\Gestalt\SimpleFilterChain;
use ThreeStreams\Defence\Handler\TerminateScriptHandler;
use ThreeStreams\Defence\PhpFunctionsWrapper;
use ThreeStreams\Defence\Defence;
use ThreeStreams\Defence\Filter\SuspiciousUserAgentHeaderFilter;

class DefenceFactory
{
    private function createTerminateScriptHandler(): TerminateScriptHandler
    {
        return new TerminateScriptHandler(new PhpFunctionsWrapper(), new HttpResponseFactory());
    }

    /**
     * Creates an instance of `Defence` containing no filters, and a handler that will immediately send a "Forbidden"
     * response and terminate the script.
     */
    public function createDefaultDefence(): Defence
    {
        return new Defence(new SimpleFilterChain(), $this->createTerminateScriptHandler());
    }

    /**
     * Creates an instance of `Defence` in the default configuration and then adds all filters that require no
     * set up.
     */
    public function createDefaultDefenceWithBasicFilters(): Defence
    {
        $defence = $this->createDefaultDefence();

        $defence
            ->getFilterChain()
            ->appendFilter(new SuspiciousUserAgentHeaderFilter())
        ;

        return $defence;
    }
}
