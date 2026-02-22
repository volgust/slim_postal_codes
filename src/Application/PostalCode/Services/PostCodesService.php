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
     * @param array $postal_codes Validated postal_codes
     * @return array ['created' => [...], 'errors' => [...]]
     */
    public function create(array $postal_codes): array
    {
        $created = [];
        $errors = [];

        foreach ($postal_codes as $index => $location) {
            try {
                if ($this->repository->existsByPostCode($location['post_code'])) {
                    throw new \RuntimeException(
                        "Duplicate post_code: {$location['post_code']}"
                    );
                }

                $created[] = $this->repository->create($location);

            } catch (\RuntimeException $e) {
                $errors[] = [
                    'location_index' => $index,
                    'message' => $e->getMessage()
                ];
            }
        }

        return ['created' => $created, 'errors' => $errors];
    }
}
