<?php

declare(strict_types=1);

namespace App\Application\PostalCode\Actions;

use App\Application\Actions\Action;
use App\Application\PostalCode\DTO\ListPostCodesDTO;
use App\Application\PostalCode\Services\PostCodesService;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Action to list postal codes with optional filtering.
 *
 * Handles HTTP requests to retrieve postal codes, supports filtering by
 * post code or address, and returns paginated results as JSON.
 */
class ListPostCodesAction extends Action
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
     * Handles the request to list post codes with optional filters.
     *
     * Extracts query parameters from the request, builds a DTO,
     * performs a search using the service, and returns the results as a JSON response.
     *
     * @return \Psr\Http\Message\ResponseInterface The HTTP response containing the search results.
     */
    protected function action(): Response
    {
        $params = $this->request->getQueryParams();

        $dto = new ListPostCodesDTO(
            postCode: $params['post_code'] ?? null,
            address: $params['address'] ?? null,
            page: isset($params['page']) ? (int)$params['page'] : 1
        );

        $resources = $this->service->search($dto);

        return $this->respondWithData(
            array_map(fn($r) => $r->toArray(), $resources)
        );
    }
}
