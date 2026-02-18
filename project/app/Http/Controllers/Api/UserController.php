<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Get(
        path: '/users',
        summary: 'List all users',
        description: 'Get paginated list of all users (Admin only)',
        security: [['sanctum' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Items per page',
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Search by name or email',
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Users retrieved successfully'),
            new OA\Response(response: 403, description: 'Forbidden (Admin only)')
        ]
    )]
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');

            $query = User::with('role');

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $query->paginate($perPage);

            return response()->json($users);
        } catch (\Exception $e) {
            Log::error('Failed to fetch users: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch users'], 500);
        }
    }

    #[OA\Post(
        path: '/users',
        summary: 'Create user',
        description: 'Create a new user (Admin only)',
        security: [['sanctum' => []]],
        tags: ['Users'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['full_name', 'email', 'password', 'role_id'],
                properties: [
                    new OA\Property(property: 'full_name', type: 'string', example: 'Test User'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'test@ucrs.edu'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'password123'),
                    new OA\Property(property: 'role_id', type: 'integer', example: 3, description: '1=Admin, 2=Instructor, 3=Student'),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive', 'suspended'], example: 'active')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'User created successfully'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'full_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
                'role_id' => ['required', 'exists:roles,role_id'],
                'status' => ['sometimes', 'in:active,inactive,suspended'],
            ]);

            $user = User::create([
                'role_id' => $validated['role_id'],
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'password_hash' => Hash::make($validated['password']),
                'status' => $validated['status'] ?? 'active',
            ]);

            $user->load('role');

            AuditLog::log('create', 'User', null, $user->toArray());

            return response()->json([
                'message' => 'User created successfully',
                'data' => $user
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create user: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create user'], 500);
        }
    }

    #[OA\Get(
        path: '/users/{id}',
        summary: 'Get user',
        description: 'Get user by ID with relationships (Admin only)',
        security: [['sanctum' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'User found'),
            new OA\Response(response: 404, description: 'User not found')
        ]
    )]
    public function show(string $id)
    {
        try {
            $user = User::with(['role', 'enrollments.course', 'taughtCourses'])->findOrFail($id);
            return response()->json($user);
        } catch (\Exception $e) {
            Log::error('Failed to fetch user: ' . $e->getMessage());
            return response()->json(['message' => 'User not found'], 404);
        }
    }

    #[OA\Put(
        path: '/users/{id}',
        summary: 'Update user',
        description: 'Update user information (Admin only)',
        security: [['sanctum' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'full_name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'role_id', type: 'integer'),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive', 'suspended'])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'User updated successfully'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function update(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);
            $before = $user->toArray();

            $validated = $request->validate([
                'full_name' => ['sometimes', 'string', 'max:255'],
                'email' => ['sometimes', 'string', 'email', 'unique:users,email,' . $id . ',user_id'],
                'password' => ['sometimes', 'string', 'min:8'],
                'role_id' => ['sometimes', 'exists:roles,role_id'],
                'status' => ['sometimes', 'in:active,inactive,suspended'],
            ]);

            if (isset($validated['password'])) {
                $validated['password_hash'] = Hash::make($validated['password']);
                unset($validated['password']);
            }

            $user->update($validated);
            $user->load('role');

            AuditLog::log('update', 'User', $before, $user->fresh()->toArray());

            return response()->json([
                'message' => 'User updated successfully',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update user: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update user'], 500);
        }
    }

    #[OA\Delete(
        path: '/users/{id}',
        summary: 'Delete user',
        description: 'Delete user by ID (Admin only)',
        security: [['sanctum' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User deleted successfully')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'User not found')
        ]
    )]
    public function destroy(string $id)
    {
        try {
            $user = User::findOrFail($id);
            $before = $user->toArray();

            $user->delete();

            AuditLog::log('delete', 'User', $before, null);

            return response()->json(['message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to delete user: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete user'], 500);
        }
    }
}
