<?php declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Factory;

use ThreeStreams\Defence\Tests\AbstractTestCase;
use ThreeStreams\Defence\Factory\DefenceFactory;
use ThreeStreams\Defence\Handler\TerminateScriptHandler;
use ThreeStreams\Defence\Filter\SuspiciousUserAgentHeaderFilter;
use ThreeStreams\Defence\Defence;

class DefenceFactoryTest extends AbstractTestCase
{
    public function testCreatedefaultdefencewithbasicfiltersReturnsAPreconfiguredDefence()
    {
        $defence = (new DefenceFactory())->createDefaultDefenceWithBasicFilters();

        $this->assertInstanceOf(Defence::class, $defence);

        $filters = $defence->getFilterChain()->getFilters();

        $this->assertCount(1, $filters);
        $this->assertContainsInstanceOf(SuspiciousUserAgentHeaderFilter::class, $filters);

        $this->assertInstanceOf(TerminateScriptHandler::class, $defence->getHandler());
    }

    public function testCreatedefaultdefenceReturnsAPreconfiguredDefence()
    {
        $defence = (new DefenceFactory())->createDefaultDefence();

        $this->assertInstanceOf(Defence::class, $defence);

        $filters = $defence->getFilterChain()->getFilters();

        $this->assertCount(0, $filters);

        $this->assertInstanceOf(TerminateScriptHandler::class, $defence->getHandler());
    }
}
