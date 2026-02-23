<?php

namespace App\Application\PostalCode\Resources;

class PostCodeResource
{
    /**
     * @param string $postCode 5-digit postal code
     * @param string $postOffice Name of the post office
     * @param string $settlement Settlement name
     * @param string $district District/Raion name
     * @param string $region Region/Oblast name
     * @param int|string $apiCreated Flag indicating if the record was created via API (1 or 0)
     */
    public function __construct(
        public string $postCode,
        public string $postOffice,
        public string $settlement,
        public string $district,
        public string $region,
        public string $apiCreated
    ) {
    }

    /**
     * Create a PostCodeResource from an associative array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            postCode: $data['post_code'] ?? '',
            postOffice: $data['post_office'] ?? '',
            settlement: $data['settlement'] ?? '',
            district: $data['district'] ?? '',
            region: $data['region'] ?? '',
            apiCreated: $data['api_created'] ?? 0,
        );
    }

    /**
     * Convert the resource to an associative array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'post_code'   => $this->postCode,
            'post_office' => $this->postOffice,
            'settlement'  => $this->settlement,
            'district'    => $this->district,
            'region'      => $this->region,
            'api_created' => $this->apiCreated,
        ];
    }
}
