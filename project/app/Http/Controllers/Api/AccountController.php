<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AccountController extends Controller
{
    #[OA\Get(
        path: '/account/profile',
        summary: 'Get user profile',
        description: 'Get current user profile information',
        security: [['sanctum' => []]],
        tags: ['Account'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user_id', type: 'integer'),
                        new OA\Property(property: 'full_name', type: 'string'),
                        new OA\Property(property: 'email', type: 'string'),
                        new OA\Property(property: 'role', type: 'string'),
                        new OA\Property(property: 'role_id', type: 'integer'),
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function profile(Request $request)
    {
        try {
            $user = $request->user();
            $user->load('role');

            return response()->json([
                'user_id' => $user->user_id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role->name,
                'role_id' => $user->role_id,
                'status' => $user->status,
                'created_at' => $user->created_at,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch profile: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch profile'], 500);
        }
    }

    #[OA\Put(
        path: '/account/profile',
        summary: 'Update user profile',
        description: 'Update current user profile information',
        security: [['sanctum' => []]],
        tags: ['Account'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'full_name', type: 'string', example: 'John Doe Updated'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'newemail@ucrs.edu')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            $before = $user->toArray();

            $validated = $request->validate([
                'full_name' => ['sometimes', 'string', 'max:255'],
                'email' => ['sometimes', 'string', 'email', 'unique:users,email,' . $user->user_id . ',user_id'],
            ]);

            $user->update($validated);

            AuditLog::log('update', 'Account', $before, $user->fresh()->toArray());

            return response()->json([
                'message' => 'Profile updated successfully',
                'data' => $user->fresh()->load('role')
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update profile: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update profile'], 500);
        }
    }

    #[OA\Put(
        path: '/account/password',
        summary: 'Change password',
        description: 'Change user account password',
        security: [['sanctum' => []]],
        tags: ['Account'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'new_password', 'new_password_confirmation'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string', format: 'password'),
                    new OA\Property(property: 'new_password', type: 'string', format: 'password', minLength: 8),
                    new OA\Property(property: 'new_password_confirmation', type: 'string', format: 'password')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password changed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Password changed successfully')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function changePassword(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'current_password' => ['required', 'string'],
                'new_password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            if (!Hash::check($validated['current_password'], $user->password_hash)) {
                throw ValidationException::withMessages([
                    'current_password' => ['Current password is incorrect.'],
                ]);
            }

            $before = ['password_updated_at' => now()];
            $user->update([
                'password_hash' => Hash::make($validated['new_password']),
            ]);

            AuditLog::log('update', 'Password', $before, ['password_updated_at' => now()]);

            return response()->json([
                'message' => 'Password changed successfully'
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to change password: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to change password'], 500);
        }
    }
}
