<?php

declare(strict_types=1);

namespace App\Application\PostalCode\Actions;

use App\Application\Actions\Action;
use App\Application\PostalCode\Requests\CreatePostCodeRequest;
use App\Application\PostalCode\Services\PostCodesService;
use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\PostalCode\Entity\PostCode;

/**
 * Action responsible for creating one or multiple postal codes.
 *
 * This action handles HTTP requests to create postal codes. It:
 *   - Parses and validates the request body using CreatePostCodeRequest.
 *   - Converts validated data into PostCode entities.
 *   - Delegates creation to PostCodesService.
 *   - Returns an HTTP response indicating success, conflict, or validation error.
 *
 */
class CreatePostCodeAction extends Action
{
    /**
     * @param PostCodesService $service
     */
    public function __construct(
        private PostCodesService $service,
    ) {
    }

    /**
     * Handles creation single or multiple post codes.
     *
     * Parses the request body, validates the input using CreatePostCodeRequest,
     * converts each validated item into a PostCode entity, and passes them to
     * the PostCodesService for creation. Returns a JSON response with the result.
     *
     * - Returns HTTP 201 if at least one post code was created.
     * - Returns HTTP 409 if no new post codes were created.
     * - Returns HTTP 422 if validation fails.
     *
     * @return \Psr\Http\Message\ResponseInterface|Response The HTTP response containing
     *                                                      the creation result.
     *
     * @throws \InvalidArgumentException If the input data is invalid.
     */
    protected function action(): Response
    {
        $data = $this->request->getParsedBody();
        $request = new CreatePostCodeRequest($data);

        try {
            $validatedList = $request->validate(); // array of arrays

            $entities = [];

            foreach ($validatedList as $item) {
                $entities[] = new PostCode(
                    null,
                    $item['region'],
                    $item['district'],
                    $item['settlement'],
                    $item['post_office'],
                    $item['post_code'],
                    $item['api_created']
                );
            }

            $result = $this->service->create($entities);

            // If at least one created → 201
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
