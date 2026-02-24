<?php

namespace App\Domain\PostalCode\Contracts;

use App\Domain\PostalCode\Entity\PostCode;

interface PostCodeRepositoryInterface
{
    public function findByPostCode(string $postCode): ?PostCode;

    public function searchByAddress(string $address): array;

    public function paginate(int $limit, int $page): array;

    public function create(PostCode $postCode): PostCode;

    public function findById(int $id): ?PostCode;

    public function existsByPostCode(string $postCode): bool;

    public function deleteByPostCodes(array $postCodes): int;
}
