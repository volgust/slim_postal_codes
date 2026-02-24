<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Application\PostalCode\Actions\CreatePostCodeAction;
use App\Application\PostalCode\Actions\DeletePostCodeAction;
use App\Application\PostalCode\Actions\ListPostCodesAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Application\PostalCode\Actions\DeleteMultiplePostCodesAction;
use App\Application\PostalCode\Actions\GetPostCodeAction;
use App\Application\Middleware\AuthMiddleware;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    $app->group('/api/post-codes', function ($group) {
        $group->get('', ListPostCodesAction::class);
        $group->get('/{post_code}', GetPostCodeAction::class);
        $group->post('', CreatePostCodeAction::class);

        // Delete single
        $group->delete('/{post_code}', DeletePostCodeAction::class);

        // Delete multiple
        $group->delete('', DeleteMultiplePostCodesAction::class);
    })->add(AuthMiddleware::class);
};
