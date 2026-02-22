<?php

declare(strict_types=1);

namespace App\Application\PostalCode\Actions;

use App\Application\Actions\Action;
use App\Application\PostalCode\Requests\CreatePostCodeRequest;
use App\Application\PostalCode\Services\PostCodesService;
use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\PostalCode\Entity\PostCode;

class CreatePostCodeAction extends Action
{
    public function __construct(
        private PostCodesService $service,
    ) {
    }

    protected function action(): Response
    {
        $data = $this->request->getParsedBody();
        $request = new CreatePostCodeRequest($data);

        try {
            $validatedList = $request->validate(); // array of arrays

            $entities = [];

            foreach ($validatedList as $item) {
                $entities[] = new PostCode(
                    null,
                    $item['region'],
                    $item['district'],
                    $item['settlement'],
                    $item['post_office'],
                    $item['post_code'],
                    $item['api_created']
                );
            }

            $result = $this->service->create($entities);

            // If at least one created → 201
            $status = !empty($result['created']) ? 201 : 409;

            return $this->respondWithData($result, $status);

        } catch (\InvalidArgumentException $e) {
            return $this->respondWithData(
                ['errors' => json_decode($e->getMessage(), true)],
                422
            );
        }
    }
}
