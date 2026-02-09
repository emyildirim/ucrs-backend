<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
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
