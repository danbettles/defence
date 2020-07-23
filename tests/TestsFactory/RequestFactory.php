<?php declare(strict_types=1);

namespace ThreeStreams\Defence\Tests\TestsFactory;

use Symfony\Component\HttpFoundation\Request;

class RequestFactory
{
    public function createWithHeader(string $name, $value): Request
    {
        $request = Request::createFromGlobals();
        $request->headers->set($name, $value);

        return $request;
    }

    public function createPost(array $parameters = []): Request
    {
        $request = Request::createFromGlobals();
        $request->setMethod(Request::METHOD_POST);

        foreach ($parameters as $name => $value) {
            $request->request->set((string) $name, $value);
        }

        return $request;
    }

    public function createGet(array $parameters = []): Request
    {
        $request = Request::createFromGlobals();
        $request->setMethod(Request::METHOD_GET);

        foreach ($parameters as $name => $value) {
            $request->query->set((string) $name, $value);
        }

        return $request;
    }
}
