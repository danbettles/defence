<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\TestsFactory;

use Symfony\Component\HttpFoundation\Request;

class RequestFactory
{
    public function createWithHeader($name, $value): Request
    {
        $request = Request::createFromGlobals();
        $request->headers->set($name, $value);

        return $request;
    }

    public function createPost(): Request
    {
        $request = Request::createFromGlobals();
        $request->setMethod(Request::METHOD_POST);

        return $request;
    }

    public function createWithGetParameters(array $parameters): Request
    {
        $request = Request::createFromGlobals();

        foreach ($parameters as $name => $value) {
            $request->query->set($name, $value);
        }

        return $request;
    }
}
