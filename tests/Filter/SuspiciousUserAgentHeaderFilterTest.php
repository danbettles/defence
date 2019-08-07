<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Filter;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use ThreeStreams\Defence\Filter\SuspiciousUserAgentHeaderFilter;
use ThreeStreams\Defence\Filter\FilterInterface;
use ThreeStreams\Defence\Logger\NullLogger;
use ThreeStreams\Defence\Envelope;
use ThreeStreams\Defence\Tests\TestsFactory\RequestFactory;
use ReflectionClass;

class SuspiciousUserAgentHeaderFilterTest extends TestCase
{
    public function testImplementsFilterinterface()
    {
        $reflectionClass = new ReflectionClass(SuspiciousUserAgentHeaderFilter::class);

        $this->assertTrue($reflectionClass->implementsInterface(FilterInterface::class));
    }

    public function providesRequestsWithSuspiciousUserAgentHeader(): array
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
        ], [
            true,
            $requestFactory->createWithHeader('User-Agent', '-'),
        ], [
            true,
            $requestFactory->createWithHeader('User-Agent', ' - '),
        ]];
    }

    /**
     * @dataProvider providesRequestsWithSuspiciousUserAgentHeader
     */
    public function testInvokeReturnsTrueIfTheUserAgentHeaderIsSuspicious($expected, $request)
    {
        $envelope = new Envelope($request, new NullLogger());
        $filter = new SuspiciousUserAgentHeaderFilter();

        $this->assertSame($expected, $filter($envelope));
    }

    public function testInvokeAddsALogToTheEnvelope()
    {
        $request = (new RequestFactory())->createWithHeader('User-Agent', '');
        $logger = new NullLogger();

        $envelope = $this
            ->getMockBuilder(Envelope::class)
            ->setConstructorArgs([$request, $logger])
            ->setMethods(['addLog'])
            ->getMock()
        ;

        $envelope
            ->expects($this->once())
            ->method('addLog')
            ->with($this->equalTo('The request has a suspicious UA string.'))
        ;

        $filter = new SuspiciousUserAgentHeaderFilter();
        $requestSuspicious = $filter($envelope);

        $this->assertTrue($requestSuspicious);
    }
}
