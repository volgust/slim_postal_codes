<?php

declare(strict_types=1);

namespace App\Domain\PostalCode\Entity;

class PostCode
{
    private ?int $id;
    private string $region;
    private string $district;
    private string $settlement;
    private string $postOffice;
    private string $postCode;
    private int $apiCreated;

    public function __construct(
        ?int $id,
        string $region,
        string $district,
        string $settlement,
        string $postOffice,
        string $postCode,
        int $apiCreated = 0
    ) {
        $this->assertValidPostCode($postCode);

        $this->id = $id;
        $this->region = trim($region);
        $this->district = trim($district);
        $this->settlement = trim($settlement);
        $this->postOffice = trim($postOffice);
        $this->postCode = $postCode;
        $this->apiCreated = $apiCreated;
    }

    private function assertValidPostCode(string $postCode): void
    {
        if (!preg_match('/^\d{5}$/', $postCode)) {
            throw new \InvalidArgumentException('Post code must be exactly 5 digits');
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPostCode(): string
    {
        return $this->postCode;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'region' => $this->region,
            'district' => $this->district,
            'settlement' => $this->settlement,
            'post_office' => $this->postOffice,
            'post_code' => $this->postCode,
            'api_created' => $this->apiCreated,
        ];
    }
}
