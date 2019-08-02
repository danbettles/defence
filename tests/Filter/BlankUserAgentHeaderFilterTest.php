<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Filter;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use ThreeStreams\Defence\Filter\BlankUserAgentHeaderFilter;
use ThreeStreams\Defence\Filter\FilterInterface;
use ThreeStreams\Defence\Logger;
use ThreeStreams\Defence\Envelope;
use ThreeStreams\Defence\Tests\TestsFactory\RequestFactory;
use ReflectionClass;

class BlankUserAgentHeaderFilterTest extends TestCase
{
    public function testImplementsFilterinterface()
    {
        $reflectionClass = new ReflectionClass(BlankUserAgentHeaderFilter::class);

        $this->assertTrue($reflectionClass->implementsInterface(FilterInterface::class));
    }

    public function providesRequestsWithBlankUserAgentHeader(): array
    {
        $requestFactory = new RequestFactory();

        return [[
            true,
            $requestFactory->createWithHeader('User-Agent', ''),
        ], [
            false,
            $requestFactory->createWithHeader('User-Agent', 'Something'),
        ], [
            true,
            $requestFactory->createWithHeader('user-agent', ''),
        ], [
            false,
            $requestFactory->createWithHeader('user-agent', 'Something'),
        ], [
            true,
            $requestFactory->createWithHeader('user-agent', ' '),
        ], [
            true,
            $requestFactory->createWithHeader('user-agent', '   '),  //Much whitespace.
        ], [
            true,
            Request::createFromGlobals(),  //No `User-Agent` header at all.
        ]];
    }

    /**
     * @dataProvider providesRequestsWithBlankUserAgentHeader
     */
    public function testInvokeReturnsTrueIfTheUserAgentHeaderIsBlank($expected, $request)
    {
        $logger = new Logger();
        $envelope = new Envelope($request, $logger);

        $filter = new BlankUserAgentHeaderFilter();

        $this->assertSame($expected, $filter($envelope));
    }

    public function testInvokeAddsALogToTheEnvelope()
    {
        $request = (new RequestFactory())->createWithHeader('User-Agent', '');
        $logger = new Logger();

        $envelope = $this
            ->getMockBuilder(Envelope::class)
            ->setConstructorArgs([$request, $logger])
            ->setMethods(['addLog'])
            ->getMock()
        ;

        $envelope
            ->expects($this->once())
            ->method('addLog')
            ->with($this->equalTo('The request has no UA string.'))
        ;

        $filter = new BlankUserAgentHeaderFilter();
        $requestSuspicious = $filter($envelope);

        $this->assertTrue($requestSuspicious);
    }
}
