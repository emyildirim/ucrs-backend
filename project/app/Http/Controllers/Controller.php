<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="University Course Registration System API",
 *     description="API for course management, student registration, and authentication",
 *     @OA\Contact(email="admin@ucrs.edu"),
 *     @OA\License(name="MIT")
 * )
 * 
 * @OA\Server(url="http://localhost:8000", description="Development")
 * @OA\Server(url="http://127.0.0.1:8000", description="Alternative")
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token"
 * )
 * 
 * @OA\Tag(name="Authentication", description="Authentication endpoints")
 * @OA\Tag(name="General", description="General endpoints")
 */
abstract class Controller
{
    //
}
