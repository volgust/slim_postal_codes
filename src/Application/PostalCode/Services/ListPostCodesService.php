<?php

namespace App\Application\PostalCode\Services;

use App\Application\PostalCode\DTO\ListPostCodesDTO;
use App\Application\PostalCode\Resources\PostCodeResource;
use App\Domain\PostalCode\Contracts\PostCodeRepositoryInterface;

class ListPostCodesService
{
    public function __construct(
        private PostCodeRepositoryInterface $repository
    ) {
    }

    public function execute(ListPostCodesDTO $request): array
    {
        $results = [];

        if ($request->postCode) {
            $row = $this->repository->findByPostCode($request->postCode);
            if ($row) {
                $results[] = PostCodeResource::fromArray($row);
            }
            return $results;
        }

        if ($request->address) {
            $rows = $this->repository->searchByAddress($request->address);
            foreach ($rows as $row) {
                $results[] = PostCodeResource::fromArray($row);
            }
            return $results;
        }

        $rows = $this->repository->paginate(50, $request->page);
        foreach ($rows as $row) {
            $results[] = PostCodeResource::fromArray($row);
        }

        return $results;
    }
}
