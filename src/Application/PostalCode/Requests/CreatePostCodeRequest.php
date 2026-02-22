<?php

declare(strict_types=1);

namespace App\Application\PostalCode\Requests;

use InvalidArgumentException;

class CreatePostCodeRequest
{
    private array $data;

    // Declare errors upfront
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Validate input data (single or multiple postal_codes)
     *
     * @return array Validated data
     * @throws \InvalidArgumentException
     */
    public function validate(): array
    {
        $postal_codes = $this->data;

        // Wrap single location into array if needed
        if (!isset($postal_codes[0]) || !is_array($postal_codes[0])) {
            $postal_codes = [$postal_codes];
        }

        $validated = [];

        foreach ($postal_codes as $index => $location) {
            $this->data = $location;
            $this->errors = [];

            $this->validateRequired('region');
            $this->validateRequired('district');
            $this->validateRequired('settlement');
            $this->validateRequired('post_office');
            $this->validatePostCode('post_code');

            if (!empty($this->errors)) {
                throw new \InvalidArgumentException(json_encode([
                    'location_index' => $index,
                    'errors' => $this->errors
                ]));
            }

            $validated[] = [
                'region'      => trim($location['region']),
                'district'    => trim($location['district']),
                'settlement'  => trim($location['settlement']),
                'post_office' => trim($location['post_office']),
                'post_code'   => trim($location['post_code']),
                'api_created' => $location['api_created'] ?? 0,
            ];
        }

        return $validated;
    }

    private function validateRequired(string $field): void
    {
        if (empty($this->data[$field]) || !is_string($this->data[$field])) {
            $this->errors[$field] = ucfirst($field) . ' is required and must be a string';
        }
    }

    private function validatePostCode(string $field): void
    {
        if (!isset($this->data[$field]) || !preg_match('/^\d{5}$/', $this->data[$field])) {
            $this->errors[$field] = 'Post code must be exactly 5 digits';
        }
    }
}
