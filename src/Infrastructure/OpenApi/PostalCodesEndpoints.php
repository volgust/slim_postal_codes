<?php

namespace App\Infrastructure\OpenApi;

use OpenApi\Attributes as OA;

class PostalCodesEndpoints
{
    #[OA\Get(
        path: "/api/post-codes",
        summary: "List post codes",
        tags: ["PostCodes"],
        parameters: [
            new OA\QueryParameter(
                name: "post_code",
                schema: new OA\Schema(type: "string")
            ),
            new OA\QueryParameter(
                name: "address",
                schema: new OA\Schema(type: "string")
            ),
            new OA\QueryParameter(
                name: "page",
                schema: new OA\Schema(type: "integer", default: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of post codes",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/PostCode")
                )
            )
        ]
    )]
    public function list()
    {
    }

    #[OA\Get(
        path: "/api/post-codes/{post_code}",
        summary: "Get single post code",
        tags: ["PostCodes"],
        parameters: [
            new OA\PathParameter(
                name: "post_code",
                required: true,
                schema: new OA\Schema(type: "string", pattern: "^\d{5}$")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Post code found",
                content: new OA\JsonContent(ref: "#/components/schemas/PostCode")
            ),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function getOne()
    {
    }

    #[OA\Post(
        path: "/api/post-codes",
        summary: "Create post codes",
        tags: ["PostCodes"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/PostCode")
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Created"),
            new OA\Response(response: 409, description: "Conflict"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function create()
    {
    }

    #[OA\Delete(
        path: "/api/post-codes/{post_code}",
        summary: "Delete single post code",
        tags: ["PostCodes"],
        parameters: [
            new OA\PathParameter(
                name: "post_code",
                required: true,
                schema: new OA\Schema(type: "string", pattern: "^\d{5}$")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Deleted"),
            new OA\Response(response: 404, description: "Not found"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function deleteOne()
    {
    }

    #[OA\Delete(
        path: "/api/post-codes",
        summary: "Delete multiple post codes",
        tags: ["PostCodes"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "post_codes",
                        type: "array",
                        items: new OA\Items(type: "string", pattern: "^\d{5}$")
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Deleted"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function deleteMany()
    {
    }
}
