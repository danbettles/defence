<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Handler;

use DanBettles\Defence\Envelope;
use DanBettles\Defence\Factory\HttpResponseFactory;
use DanBettles\Defence\Handler\HandlerInterface;
use DanBettles\Defence\Handler\TerminateScriptHandler;
use DanBettles\Defence\Logger\NullLogger;
use DanBettles\Defence\PhpFunctionsWrapper;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TerminateScriptHandlerTest extends TestCase
{
    public function testImplementsHandlerinterface(): void
    {
        $reflectionClass = new ReflectionClass(TerminateScriptHandler::class);

        $this->assertTrue($reflectionClass->implementsInterface(HandlerInterface::class));
    }

    public function testConstructor(): void
    {
        $phpFunctions = new PhpFunctionsWrapper();
        $httpResponseFactory = new HttpResponseFactory();

        $handler = new TerminateScriptHandler($phpFunctions, $httpResponseFactory);

        $this->assertSame($phpFunctions, $handler->getPhpFunctionsWrapper());
        $this->assertSame($httpResponseFactory, $handler->getHttpResponseFactory());
    }

    public function testInvokeTerminatesTheScript(): void
    {
        $request = Request::createFromGlobals();

        //Create a mock response:

        $httpResponseMock = $this
            ->getMockBuilder(Response::class)
            ->onlyMethods(['prepare', 'send'])
            ->getMock()
        ;

        $httpResponseMock
            ->expects($this->once())
            ->method('prepare')
            ->with($request)
            ->will($this->returnSelf())
        ;

        //Make sure the response will be sent.
        $httpResponseMock
            ->expects($this->once())
            ->method('send')
        ;

        //Create a mock HTTP-response factory that will return our mock response:

        $httpResponseFactoryMock = $this
            ->getMockBuilder(HttpResponseFactory::class)
            ->onlyMethods(['createForbiddenResponse'])
            ->getMock()
        ;

        $httpResponseFactoryMock
            ->expects($this->once())
            ->method('createForbiddenResponse')
            ->with("We're not going to handle your request because it looks suspicious.  Please contact us if we've made a mistake.")
            ->willReturn($httpResponseMock)
        ;

        /** @var HttpResponseFactory $httpResponseFactoryMock */

        //Mock the PHP-functions wrapper so we can test if the script will be terminated.

        $phpFunctionsMock = $this
            ->getMockBuilder(PhpFunctionsWrapper::class)
            ->onlyMethods(['exit'])
            ->getMock()
        ;

        $phpFunctionsMock
            ->expects($this->once())
            ->method('exit')
            ->with(0)
        ;

        /** @var PhpFunctionsWrapper $phpFunctionsMock */

        //Run the handler:

        $envelope = new Envelope($request, new NullLogger());

        $handler = new TerminateScriptHandler($phpFunctionsMock, $httpResponseFactoryMock);
        $handler($envelope);
    }
}
