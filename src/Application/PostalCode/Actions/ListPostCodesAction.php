<?php

declare(strict_types=1);

namespace App\Application\PostalCode\Actions;

use App\Application\Actions\Action;
use App\Application\PostalCode\DTO\ListPostCodesDTO;
use App\Application\PostalCode\Services\ListPostCodesService;
use Psr\Http\Message\ResponseInterface as Response;

class ListPostCodesAction extends Action
{
    public function __construct(
        private ListPostCodesService $service
    ) {
    }

    protected function action(): Response
    {
        $params = $this->request->getQueryParams();

        $dto = new ListPostCodesDTO(
            postCode: $params['post_code'] ?? null,
            address: $params['address'] ?? null,
            page: isset($params['page']) ? (int)$params['page'] : 1
        );

        $resources = $this->service->execute($dto);

        return $this->respondWithData(
            array_map(fn($r) => $r->toArray(), $resources)
        );
    }
}
