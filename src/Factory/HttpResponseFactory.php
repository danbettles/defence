<?php
declare(strict_types=1);

namespace ThreeStreams\Defence\Factory;

use Symfony\Component\HttpFoundation\Response;

class HttpResponseFactory
{
    public function createForbidden(string $content): Response
    {
        return new Response($content, Response::HTTP_FORBIDDEN);
    }
}
