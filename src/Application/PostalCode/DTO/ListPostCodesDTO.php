<?php

namespace App\Application\PostalCode\DTO;

final class ListPostCodesDTO
{
    public function __construct(
        public ?string $postCode = null,
        public ?string $address = null,
        public int $page = 1
    ) {
    }
}
