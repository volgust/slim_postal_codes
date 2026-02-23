<?php

declare(strict_types=1);

namespace App\Domain\PostalCode\Entity;

/**
 * Represents a postal code entity.
 *
 * Encapsulates details of a postal code, including region, district,
 * settlement, post office, and API-created flag. Ensures the post code
 * is exactly 5 digits.
 */
class PostCode
{
    private ?int $id;
    private string $region;
    private string $district;
    private string $settlement;
    private string $postOffice;
    private string $postCode;
    private int $apiCreated;

    /**
     * Constructor.
     *
     * @param int|null $id Database ID, null if not persisted yet.
     * @param string $region Region/Oblast name.
     * @param string $district District/Raion name.
     * @param string $settlement Settlement name.
     * @param string $postOffice Name of the post office.
     * @param string $postCode 5-digit postal code.
     * @param int $apiCreated Flag indicating if the record was created via API (default 0).
     *
     * @throws \InvalidArgumentException If the post code is not exactly 5 digits.
     */
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

    /**
     * Validates that a postal code is exactly 5 digits.
     *
     * @param string $postCode The postal code to validate.
     *
     * @throws \InvalidArgumentException If the postal code is not exactly 5 digits.
     */
    private function assertValidPostCode(string $postCode): void
    {
        if (!preg_match('/^\d{5}$/', $postCode)) {
            throw new \InvalidArgumentException('Post code must be exactly 5 digits');
        }
    }

    /**
     * Get the ID of the postal code.
     *
     * @return int|null Database ID or null if not persisted.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the 5-digit postal code value.
     *
     * @return string
     */
    public function getPostCode(): string
    {
        return $this->postCode;
    }


    /**
     * Convert the entity to an associative array.
     *
     * @return array Array representation of the postal code.
     */
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
