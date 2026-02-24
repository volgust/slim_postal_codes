<?php

namespace App\Infrastructure\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "PostCode",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "region", type: "string", example: "Kyiv"),
        new OA\Property(property: "district", type: "string", example: "Shevchenkivskyi"),
        new OA\Property(property: "settlement", type: "string", example: "Kyiv"),
        new OA\Property(property: "post_office", type: "string", example: "Central Office"),
        new OA\Property(property: "post_code", type: "string", example: "01001"),
        new OA\Property(property: "api_created", type: "integer", example: 1),
    ]
)]
class PostCodeSchema {}