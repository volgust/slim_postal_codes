<?php

namespace App\Domain\PostalCode\Contracts;

interface PostCodeRepositoryInterface
{
    public function findByPostCode(string $postCode): ?array;

    public function searchByAddress(string $address): array;

    public function paginate(int $limit, int $page): array;
}