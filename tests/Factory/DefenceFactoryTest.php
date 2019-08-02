<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Factory;

use ThreeStreams\Defence\Tests\AbstractTestCase;
use ThreeStreams\Defence\Factory\DefenceFactory;
use ThreeStreams\Defence\Handler\TerminateScriptHandler;
use ThreeStreams\Defence\Filter\BlankUserAgentHeaderFilter;
use ThreeStreams\Defence\Defence;

class DefenceFactoryTest extends AbstractTestCase
{
    public function testCreatedefaultReturnsAPreconfiguredDefence()
    {
        $defence = (new DefenceFactory())->createDefault();

        $this->assertInstanceOf(Defence::class, $defence);

        $filters = $defence->getFilterChain()->getFilters();

        $this->assertCount(1, $filters);
        $this->assertContainsInstanceOf(BlankUserAgentHeaderFilter::class, $filters);

        $this->assertInstanceOf(TerminateScriptHandler::class, $defence->getHandler());
    }
}
