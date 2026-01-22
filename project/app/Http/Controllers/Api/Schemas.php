<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Schema(
 *     schema="User",
 *     title="User",
 *     description="User model",
 *     required={"id", "name", "email"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="User ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="User's full name",
 *         example="John Doe"
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         description="User's email address",
 *         example="john@example.com"
 *     ),
 *     @OA\Property(
 *         property="email_verified_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Email verification timestamp",
 *         example="2026-01-21T20:53:19.000000Z"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Account creation timestamp",
 *         example="2026-01-21T20:53:19.000000Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Last update timestamp",
 *         example="2026-01-21T20:53:19.000000Z"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     title="Validation Error",
 *     description="Validation error response",
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         description="Error message",
 *         example="The given data was invalid."
 *     ),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         description="Field-specific validation errors",
 *         @OA\AdditionalProperties(
 *             type="array",
 *             @OA\Items(type="string")
 *         ),
 *         example={"email": {"The email field is required."}}
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AuthResponse",
 *     title="Authentication Response",
 *     description="Successful authentication response",
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/User"
 *     ),
 *     @OA\Property(
 *         property="access_token",
 *         type="string",
 *         description="Bearer access token",
 *         example="1|abcdef123456789..."
 *     ),
 *     @OA\Property(
 *         property="token_type",
 *         type="string",
 *         description="Token type",
 *         example="Bearer"
 *     )
 * )
 */
class Schemas
{
    // This class only contains OpenAPI schema definitions
}
