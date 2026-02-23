<?php

declare(strict_types=1);

namespace App\Application\PostalCode\Actions;

use App\Application\PostalCode\Services\PostCodesService;
use App\Application\Actions\Action;
use App\Application\PostalCode\Requests\CreatePostCodeRequest;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Action to delete multiple postal codes in a single request.
 *
 * Expects a JSON body with a 'post_codes' array of 5-digit codes.
 * Validates the input and delegates deletion to PostCodesService.
 */
class DeleteMultiplePostCodesAction extends Action
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
     * Deletes multiple post codes in a single request.
     *
     * Expects a JSON body with a 'post_codes' array containing 5-digit post codes.
     * Validates each code for correct format and scalar type, and calls the service
     * to perform deletion. Returns a JSON response with the result or validation errors.
     *
     * @return \Psr\Http\Message\ResponseInterface|Response The HTTP response containing
     *                                                      the result of the deletion.
     *
     * @throws \InvalidArgumentException If the input data is missing or invalid.
     */
    protected function action(): Response
    {
        $data = $this->request->getParsedBody();

        if (!isset($data['post_codes']) || !is_array($data['post_codes'])) {
            return $this->respondWithData(
                ['errors' => 'post_codes array is required'],
                422
            );
        }

        foreach ($data['post_codes'] as $index => $code) {
            if (!is_scalar($code)) {
                return $this->respondWithData(
                    ['errors' => "Invalid post_code at index {$index}"],
                    422
                );
            }

            $code = (string) $code;

            if (!preg_match('/^\d{5}$/', $code)) {
                return $this->respondWithData(
                    ['errors' => "Invalid post_code: {$code}"],
                    422
                );
            }
        }

        $result = $this->service->delete($data['post_codes']);

        return $this->respondWithData($result, 200);
    }
}
