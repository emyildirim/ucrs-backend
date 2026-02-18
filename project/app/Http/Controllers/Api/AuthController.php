<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/auth/register',
        summary: 'Register new user',
        description: 'Register a new user account (defaults to Student role)',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['full_name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'full_name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@ucrs.edu'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'role_id', type: 'integer', example: 3, description: 'Optional: 1=Admin, 2=Instructor, 3=Student')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'user',
                            properties: [
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'full_name', type: 'string', example: 'John Doe'),
                                new OA\Property(property: 'email', type: 'string', example: 'john@ucrs.edu'),
                                new OA\Property(property: 'role', type: 'string', example: 'Student'),
                                new OA\Property(property: 'status', type: 'string', example: 'active')
                            ],
                            type: 'object'
                        ),
                        new OA\Property(property: 'access_token', type: 'string'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function register(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['sometimes', 'integer', 'exists:roles,role_id'],
        ]);

        $user = User::create([
            'role_id' => $validated['role_id'] ?? 3,
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'status' => 'active',
        ]);

        $user->load('role');
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => [
                'user_id' => $user->user_id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role->name,
                'status' => $user->status,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    #[OA\Post(
        path: '/auth/login',
        summary: 'Login user',
        description: 'Authenticate user and return access token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@ucrs.edu'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'user',
                            properties: [
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'full_name', type: 'string', example: 'Admin User'),
                                new OA\Property(property: 'email', type: 'string', example: 'admin@ucrs.edu'),
                                new OA\Property(property: 'role', type: 'string', example: 'Admin'),
                                new OA\Property(property: 'status', type: 'string', example: 'active')
                            ],
                            type: 'object'
                        ),
                        new OA\Property(property: 'access_token', type: 'string', example: '1|abc123...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Invalid credentials')
        ]
    )]
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Account is not active.'],
            ]);
        }

        $user->load('role');
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => [
                'user_id' => $user->user_id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role->name,
                'status' => $user->status,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    #[OA\Post(
        path: '/auth/logout',
        summary: 'Logout user',
        description: 'Logout user from current device',
        security: [['sanctum' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Logged out successfully')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    #[OA\Post(
        path: '/auth/logout-all',
        summary: 'Logout all devices',
        description: 'Logout user from all devices by invalidating all tokens',
        security: [['sanctum' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logged out from all devices',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Logged out from all devices')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out from all devices',
        ]);
    }

    #[OA\Get(
        path: '/auth/me',
        summary: 'Get current user',
        description: 'Get authenticated user information',
        security: [['sanctum' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Current user information',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user_id', type: 'integer', example: 1),
                        new OA\Property(property: 'full_name', type: 'string', example: 'Admin User'),
                        new OA\Property(property: 'email', type: 'string', example: 'admin@ucrs.edu'),
                        new OA\Property(property: 'role', type: 'string', example: 'Admin'),
                        new OA\Property(property: 'status', type: 'string', example: 'active'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load('role');

        return response()->json([
            'user_id' => $user->user_id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'role' => $user->role->name,
            'status' => $user->status,
            'created_at' => $user->created_at,
        ]);
    }
}
