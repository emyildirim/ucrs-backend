<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'UCRS API Documentation',
    description: 'University Course Registration System - Complete API documentation'
)]
#[OA\Server(
    url: 'http://localhost:8000/api',
    description: 'Local Development Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Enter token in format: Bearer {token}'
)]
#[OA\Tag(name: 'Authentication', description: 'User authentication endpoints')]
#[OA\Tag(name: 'Account', description: 'Account management')]
#[OA\Tag(name: 'Users', description: 'User management (Admin only)')]
#[OA\Tag(name: 'Courses', description: 'Course management')]
#[OA\Tag(name: 'Enrollments', description: 'Enrollment management')]
#[OA\Tag(name: 'Assignments', description: 'Assignment management')]
#[OA\Tag(name: 'Submissions', description: 'Submission management')]
#[OA\Tag(name: 'Audit Logs', description: 'Audit logs (Admin only)')]
abstract class Controller
{
    //
}
