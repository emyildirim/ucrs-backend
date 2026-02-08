<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
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
