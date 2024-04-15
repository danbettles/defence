<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Factory;

use DanBettles\Defence\Defence;
use DanBettles\Defence\Factory\DefenceFactory;
use DanBettles\Defence\Filter\InvalidHeaderFilter;
use DanBettles\Defence\Handler\TerminateScriptHandler;
use DanBettles\Defence\Tests\AbstractTestCase;

class DefenceFactoryTest extends AbstractTestCase
{
    public function testCreatedefaultdefencewithbasicfiltersReturnsAPreconfiguredDefence(): void
    {
        $defence = (new DefenceFactory())->createDefaultDefenceWithBasicFilters();

        $this->assertInstanceOf(Defence::class, $defence);

        $filters = $defence->getFilterChain()->getFilters();

        $this->assertCount(1, $filters);
        $this->assertContainsInstanceOf(InvalidHeaderFilter::class, $filters);

        $this->assertInstanceOf(TerminateScriptHandler::class, $defence->getHandler());
    }

    public function testCreatedefaultdefenceReturnsAPreconfiguredDefence(): void
    {
        $defence = (new DefenceFactory())->createDefaultDefence();

        $this->assertInstanceOf(Defence::class, $defence);

        $filters = $defence->getFilterChain()->getFilters();

        $this->assertCount(0, $filters);

        $this->assertInstanceOf(TerminateScriptHandler::class, $defence->getHandler());
    }
}
