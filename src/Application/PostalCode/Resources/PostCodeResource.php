<?php

namespace App\Application\PostalCode\Resources;

class PostCodeResource
{
    public function __construct(
        public string $postCode,
        public string $postOffice,
        public string $settlement,
        public string $district,
        public string $region,
        public string $source
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            postCode: $data['post_code'] ?? '',
            postOffice: $data['post_office'] ?? '',
            settlement: $data['settlement'] ?? '',
            district: $data['district'] ?? '',
            region: $data['region'] ?? '',
            source: $data['source'] ?? 'archive',
        );
    }

    public function toArray(): array
    {
        return [
            'post_code'   => $this->postCode,
            'post_office' => $this->postOffice,
            'settlement'  => $this->settlement,
            'district'    => $this->district,
            'region'      => $this->region,
            'source'      => $this->source,
        ];
    }
}
