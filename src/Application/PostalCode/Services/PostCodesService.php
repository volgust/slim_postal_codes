<?php

namespace App\Application\PostalCode\Services;

use App\Application\PostalCode\DTO\ListPostCodesDTO;
use App\Application\PostalCode\Resources\PostCodeResource;
use App\Domain\PostalCode\Contracts\PostCodeRepositoryInterface;

class PostCodesService
{
    public function __construct(
        private PostCodeRepositoryInterface $repository
    ) {
    }

    public function search(ListPostCodesDTO $request): array
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

    /**
     * Create one or more postal_codes, skip duplicates
     *
     * @param array $entities Validated postal_codes
     * @return array ['created' => [...], 'errors' => [...]]
     */
    public function create(array $entities): array
    {
        $created = [];
        $errors = [];

        foreach ($entities as $index => $entity) {
            if ($this->repository->existsByPostCode($entity->getPostCode())) {
                $errors[] = [
                    'index' => $index,
                    'message' => 'Duplicate post_code: ' . $entity->getPostCode()
                ];
                continue;
            }

            $saved = $this->repository->create($entity);
            $created[] = $saved->toArray();
        }

        return [
            'created' => $created,
            'errors' => $errors
        ];
    }

    public function delete(array $postCodes): array
    {
        $deletedCount = $this->repository->deleteByPostCodes($postCodes);

        return [
            'requested' => count($postCodes),
            'deleted' => $deletedCount
        ];
    }
}
