<?php

declare(strict_types=1);

namespace DanBettles\Defence\Tests\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use DanBettles\Defence\Factory\HttpResponseFactory;

class HttpResponseFactoryTest extends TestCase
{
    public function testCreateforbiddenresponseCreatesAForbiddenResponse()
    {
        $factory = new HttpResponseFactory();
        $response = $factory->createForbiddenResponse('Lorem ipsum dolor.');

        $this->assertSame('Lorem ipsum dolor.', $response->getContent());
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
}
