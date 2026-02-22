<?php

declare(strict_types=1);

namespace App\Application\PostalCode\Actions;

use App\Application\PostalCode\Services\PostCodesService;
use App\Application\Actions\Action;
use App\Application\PostalCode\Requests\CreatePostCodeRequest;
use Psr\Http\Message\ResponseInterface as Response;

class DeleteMultiplePostCodesAction extends Action
{
    public function __construct(
        private PostCodesService $service
    ) {
    }

    protected function action(): Response
    {
        $data = $this->request->getParsedBody();

        if (!isset($data['post_codes']) || !is_array($data['post_codes'])) {
            return $this->respondWithData(
                ['errors' => 'post_codes array is required'],
                422
            );
        }

        foreach ($data['post_codes'] as $index => $code) {
            if (!is_scalar($code)) {
                return $this->respondWithData(
                    ['errors' => "Invalid post_code at index {$index}"],
                    422
                );
            }

            $code = (string) $code;

            if (!preg_match('/^\d{5}$/', $code)) {
                return $this->respondWithData(
                    ['errors' => "Invalid post_code: {$code}"],
                    422
                );
            }
        }

        $result = $this->service->delete($data['post_codes']);

        return $this->respondWithData($result, 200);
    }
}