<?php

declare(strict_types=1);

namespace App\Application\PostalCode\Actions;

use App\Application\Actions\Action;
use App\Application\PostalCode\Requests\CreatePostCodeRequest;
use App\Application\PostalCode\Services\PostCodesService;
use Psr\Http\Message\ResponseInterface as Response;

class CreatePostCodeAction extends Action
{
    public function __construct(
        private PostCodesService $service,
    ) {
    }

    protected function action(): Response
    {
        $data = $this->request->getParsedBody();
        $request = new CreatePostCodeRequest($data);

        try {
            $validated = $request->validate();
            $result = $this->service->create($validated);

            // 201 if at least one created, else 409
            $status = !empty($result['created']) ? 201 : 409;

            return $this->respondWithData($result, $status);

        } catch (\InvalidArgumentException $e) {
            return $this->respondWithData(
                ['errors' => json_decode($e->getMessage(), true)],
                422
            );
        }
    }
}
