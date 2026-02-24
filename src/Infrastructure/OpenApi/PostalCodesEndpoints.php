<?php

namespace App\Infrastructure\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: "/postal-codes",
    description: "Returns paginated postal codes with optional filtering.",
    summary: "List postal codes",
    parameters: [
        new OA\QueryParameter(
            name: "post_code",
            required: false,
            schema: new OA\Schema(type: "string")
        ),
        new OA\QueryParameter(
            name: "address",
            required: false,
            schema: new OA\Schema(type: "string")
        ),
        new OA\QueryParameter(
            name: "page",
            required: false,
            schema: new OA\Schema(type: "integer", default: 1)
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Successful response"
        )
    ]
)]
class PostalCodesEndpoints
{
}