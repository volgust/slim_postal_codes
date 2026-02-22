<?php

declare(strict_types=1);

namespace App\Application\PostalCode\Actions;

use App\Application\Actions\Action;
use App\Application\PostalCode\Services\PostCodesService;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\PostalCode\Requests\DeletePostCodeRequest;

class DeletePostCodeAction extends Action
{
    public function __construct(
        private PostCodesService $service
    ) {
    }

    protected function action(): Response
    {
        $postCode = $this->resolveArg('post_code');

        try {
            if (!preg_match('/^\d{5}$/', $postCode)) {
                throw new \InvalidArgumentException('Invalid post_code format');
            }

            $deleted = $this->service->delete([$postCode]);

            if ($deleted['deleted'] === 0) {
                return $this->respondWithData(
                    ['message' => 'Post code not found'],
                    404
                );
            }

            return $this->respondWithData($deleted, 200);
        } catch (\InvalidArgumentException $e) {
            return $this->respondWithData(
                ['errors' => $e->getMessage()],
                422
            );
        }
    }
}
