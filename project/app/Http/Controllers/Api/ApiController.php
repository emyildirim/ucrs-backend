<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/hello",
     *     summary="Hello World endpoint",
     *     description="Returns a simple hello world message with API information",
     *     tags={"General"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Hello World from University Course Registration System API"),
     *             @OA\Property(property="version", type="string", example="1.0.0"),
     *             @OA\Property(property="timestamp", type="string", format="date-time", example="2026-01-20T16:10:55+00:00")
     *         )
     *     )
     * )
     */
    public function hello()
    {
        return response()->json([
            'message' => 'Hello World from University Course Registration System API',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/user",
     *     summary="Get authenticated user",
     *     description="Returns the currently authenticated user's information",
     *     tags={"Authentication"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function user()
    {
        return request()->user();
    }
}
