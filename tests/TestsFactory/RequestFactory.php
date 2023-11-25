<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\TestsFactory;

use Symfony\Component\HttpFoundation\Request;

use function array_key_exists;
use function is_array;

/**
 * @phpstan-type Headers array<string,string>
 * @phpstan-type InputBagValue mixed[]|bool|float|int|string|null
 * @phpstan-type RequestParameters array<string,InputBagValue>
 * @phpstan-type CreateOptions array{method?:string,headers?:Headers,query?:RequestParameters,body?:RequestParameters}
 */
class RequestFactory
{
    /**
     * Creates a `GET` request by default
     *
     * @phpstan-param CreateOptions $options
     */
    public function create(array $options): Request
    {
        $request = new Request();
        $request->setMethod($options['method'] ?? Request::METHOD_GET);

        if (array_key_exists('headers', $options)) {
            foreach ($options['headers'] as $name => $value) {
                $request->headers->set($name, $value);
            }
        }

        if (array_key_exists('query', $options)) {
            foreach ($options['query'] as $name => $value) {
                $request->query->set($name, $value);
            }
        }

        if (array_key_exists('body', $options)) {
            $body = $options['body'];

            if (is_array($body)) {
                foreach ($body as $name => $value) {
                    $request->request->set($name, $value);
                }
            }
        }

        return $request;
    }

    /**
     * Creates a `GET` request
     *
     * @phpstan-param Headers $headers
     */
    public function createWithHeaders(array $headers): Request
    {
        return $this->create(['headers' => $headers]);
    }

    /**
     * @phpstan-param RequestParameters $parameters
     */
    public function createPost(array $parameters = []): Request
    {
        return $this->create([
            'method' => Request::METHOD_POST,
            'body' => $parameters,
        ]);
    }

    /**
     * @phpstan-param RequestParameters $parameters
     */
    public function createGet(array $parameters = []): Request
    {
        return $this->create([
            'method' => Request::METHOD_GET,
            'query' => $parameters,
        ]);
    }
}
