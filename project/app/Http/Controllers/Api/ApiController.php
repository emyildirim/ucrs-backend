<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/hello",
     *     summary="Health check endpoint",
     *     tags={"General"},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="version", type="string"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
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
     *     tags={"Authentication"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function user()
    {
        return request()->user();
    }
}
