<?php

namespace App\Application\PostalCode\Services;

use App\Application\PostalCode\DTO\ListPostCodesDTO;
use App\Application\PostalCode\Resources\PostCodeResource;
use App\Domain\PostalCode\Contracts\PostCodeRepositoryInterface;
use \App\Domain\PostalCode\Entity\PostCode;

/**
 * Service for managing postal codes.
 *
 * Provides search, creation, and deletion operations.
 */
class PostCodesService
{
    /**
     * Constructor.
     *
     * @param PostCodeRepositoryInterface $repository Repository for CRUD operations on postal codes.
     */
    public function __construct(
        private PostCodeRepositoryInterface $repository
    ) {
    }

    /**
     * Searches postal codes by post code or address, or returns paginated list.
     *
     * @param ListPostCodesDTO $request DTO containing search parameters:
     *                                  - postCode: optional, specific post code to find
     *                                  - address: optional, address string to search
     *                                  - page: page number for pagination
     *
     * @return PostCodeResource[] Array of PostCodeResource objects matching the query.
     */
    public function search(ListPostCodesDTO $request): array
    {
        $results = [];

        if ($request->postCode) {
            $row = $this->repository->findByPostCode($request->postCode);
            if ($row) {
                $results[] = $row;
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

    public function find(string $postCode): ?PostCode
    {
        return $this->repository->findByPostCode($postCode);
    }

    /**
     * Create one or more postal codes, skipping duplicates.
     *
     * @param array $entities Array of validated postal code entities.
     *
     * @return array{
     *     created: array<array>, // list of successfully created postal codes
     *     errors: array<array{index:int, message:string}> // list of errors (duplicates)
     * }
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

    /**
     * Deletes postal codes by their values.
     *
     * @param string[] $postCodes Array of post code strings to delete.
     *
     * @return array{
     *     requested: int, // total requested to delete
     *     deleted: int    // number of post codes actually deleted
     * }
     */
    public function delete(array $postCodes): array
    {
        $deletedCount = $this->repository->deleteByPostCodes($postCodes);

        return [
            'requested' => count($postCodes),
            'deleted' => $deletedCount
        ];
    }
}
