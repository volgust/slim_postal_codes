<?php

declare(strict_types=1);

namespace App\Application\PostalCode\Actions;

use App\Application\Actions\Action;
use App\Application\PostalCode\Services\PostCodesService;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Action to delete a single postal code.
 *
 * Resolves the 'post_code' argument from the request, validates it,
 * and delegates deletion to PostCodesService. Returns JSON indicating
 * success, not found, or validation errors.
 */
class DeletePostCodeAction extends Action
{
    /**
     * Constructor.
     *
     * Injects the PostCodesService dependency.
     *
     * @param \App\Application\PostalCode\Services\PostCodesService $service Used to handle post code operations.
     */
    public function __construct(
        private PostCodesService $service
    ) {
    }

    /**
     * Deletes a post code by its value.
     *
     * Resolves the 'post_code' argument from the request, validates its format,
     * and attempts to delete it using the PostCodesService. Returns a JSON response
     * indicating success, not found, or validation errors.
     *
     * @return \Psr\Http\Message\ResponseInterface|Response The HTTP response containing
     *                                                      the result of the deletion.
     *
     * @throws \InvalidArgumentException If the post code format is invalid.
     */
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
