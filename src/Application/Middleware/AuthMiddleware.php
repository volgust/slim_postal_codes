<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware implements MiddlewareInterface
{
    private string $validApiKey;

    public function __construct(string $validApiKey)
    {
        $this->validApiKey = $validApiKey;
    }

    public function process(Request $request, Handler $handler): Response
    {
        $apiKey = $request->getHeaderLine('X-API-KEY');

        if (!$apiKey || !hash_equals($this->validApiKey, $apiKey)) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'error' => 'Forbidden',
                'message' => 'Invalid or missing API key'
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        return $handler->handle($request);
    }
}
