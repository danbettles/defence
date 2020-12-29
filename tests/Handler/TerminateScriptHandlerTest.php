<?php

declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\Handler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ThreeStreams\Defence\Handler\TerminateScriptHandler;
use ThreeStreams\Defence\Handler\HandlerInterface;
use ThreeStreams\Defence\PhpFunctionsWrapper;
use ThreeStreams\Defence\Logger\NullLogger;
use ThreeStreams\Defence\Envelope;
use ThreeStreams\Defence\Factory\HttpResponseFactory;
use ReflectionClass;

class TerminateScriptHandlerTest extends TestCase
{
    public function testImplementsHandlerinterface()
    {
        $reflectionClass = new ReflectionClass(TerminateScriptHandler::class);

        $this->assertTrue($reflectionClass->implementsInterface(HandlerInterface::class));
    }

    public function testConstructor()
    {
        $phpFunctions = new PhpFunctionsWrapper();
        $httpResponseFactory = new HttpResponseFactory();

        $handler = new TerminateScriptHandler($phpFunctions, $httpResponseFactory);

        $this->assertSame($phpFunctions, $handler->getPhpFunctionsWrapper());
        $this->assertSame($httpResponseFactory, $handler->getHttpResponseFactory());
    }

    public function testInvokeTerminatesTheScript()
    {
        $request = Request::createFromGlobals();

        //Create a mock response:

        $httpResponseMock = $this
            ->getMockBuilder(Response::class)
            ->setMethods(['prepare', 'send'])
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
            ->setMethods(['createForbiddenResponse'])
            ->getMock()
        ;

        $httpResponseFactoryMock
            ->expects($this->once())
            ->method('createForbiddenResponse')
            ->with("We're not going to handle your request because it looks suspicious.  Please contact us if we've made a mistake.")
            ->willReturn($httpResponseMock)
        ;

        //Mock the PHP-functions wrapper so we can test if the script will be terminated.

        $phpFunctionsMock = $this
            ->getMockBuilder(PhpFunctionsWrapper::class)
            ->setMethods(['exit'])
            ->getMock()
        ;

        $phpFunctionsMock
            ->expects($this->once())
            ->method('exit')
            ->with(0)
        ;

        //Run the handler:

        $envelope = new Envelope($request, new NullLogger());

        $handler = new TerminateScriptHandler($phpFunctionsMock, $httpResponseFactoryMock);
        $handler($envelope);
    }
}
