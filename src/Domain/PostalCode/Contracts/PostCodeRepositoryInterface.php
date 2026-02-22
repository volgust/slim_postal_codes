<?php

namespace App\Domain\PostalCode\Contracts;

interface PostCodeRepositoryInterface
{
    public function findByPostCode(string $postCode): ?array;

    public function searchByAddress(string $address): array;

    public function paginate(int $limit, int $page): array;

    public function create(array $data): array;

    public function findById(int $id): array;

    public function existsByPostCode(string $postCode): bool;

    public function deleteByPostCodes(array $postCodes): int;


}