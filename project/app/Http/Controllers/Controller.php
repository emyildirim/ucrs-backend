<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="University Course Registration System API",
 *     description="RESTful API for the University Course Registration System (UCRS). This API provides endpoints for authentication, course management, student registration, and administrative functions.",
 *     @OA\Contact(
 *         name="UCRS API Support",
 *         email="admin@ucrs.edu",
 *         url="https://ucrs.edu/support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local Development Server"
 * )
 * 
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Alternative Local Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token",
 *     description="Enter your Bearer token in the format: Bearer {token}"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and token management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="General",
 *     description="General API information and health check endpoints"
 * )
 */
abstract class Controller
{
    //
}
