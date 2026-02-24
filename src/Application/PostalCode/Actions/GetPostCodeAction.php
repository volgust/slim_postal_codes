<?php

declare(strict_types=1);

namespace App\Application\PostalCode\Actions;

use App\Application\Actions\Action;
use App\Application\PostalCode\Services\PostCodesService;
use Psr\Http\Message\ResponseInterface as Response;

class GetPostCodeAction extends Action
{
    public function __construct(
        private PostCodesService $service
    ) {
    }

    protected function action(): Response
    {
        $postCode = $this->resolveArg('post_code');

        $resource = $this->service->find($postCode);

        if (!$resource) {
            return $this->respondWithError('Post code not found', 404);
        }

        return $this->respondWithData($resource->toArray());
    }
}