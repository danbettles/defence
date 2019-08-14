<?php
declare(strict_types=1);

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
     * Creates an instance of `Defence` that contains all _Defence_ filters that require no configuration, and a handler
     * that will immediately send a "Forbidden" response and terminate the script.
     */
    public function createDefaultDefence(): Defence
    {
        $filterChain = new SimpleFilterChain([
            new SuspiciousUserAgentHeaderFilter(),
        ]);

        $handler = $this->createTerminateScriptHandler();

        $defence = new Defence($filterChain, $handler);

        return $defence;
    }
}
