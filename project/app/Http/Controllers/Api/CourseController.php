<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $showAll = $request->input('show_all', false);

            $query = Course::with('instructor');

            if (!$showAll) {
                $query->active();
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            $courses = $query->paginate($perPage);

            return response()->json($courses);
        } catch (\Exception $e) {
            Log::error('Failed to fetch courses: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch courses'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'unique:courses,code'],
                'instructor_id' => ['required', 'exists:users,user_id'],
            ]);

            $course = Course::create($validated);
            
            AuditLog::log('create', 'Course', null, $course->toArray());

            return response()->json([
                'message' => 'Course created successfully',
                'data' => $course->load('instructor')
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create course: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create course'], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $course = Course::with(['instructor', 'enrollments.student', 'assignments'])->findOrFail($id);
            return response()->json($course);
        } catch (\Exception $e) {
            Log::error('Failed to fetch course: ' . $e->getMessage());
            return response()->json(['message' => 'Course not found'], 404);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $course = Course::findOrFail($id);
            
            $before = $course->toArray();

            $validated = $request->validate([
                'title' => ['sometimes', 'string', 'max:255'],
                'code' => ['sometimes', 'string', 'unique:courses,code,' . $id . ',course_id'],
                'instructor_id' => ['sometimes', 'exists:users,user_id'],
                'is_active' => ['sometimes', 'boolean'],
            ]);

            $course->update($validated);
            
            AuditLog::log('update', 'Course', $before, $course->fresh()->toArray());

            return response()->json([
                'message' => 'Course updated successfully',
                'data' => $course->load('instructor')
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update course: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update course'], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $course = Course::findOrFail($id);
            $before = $course->toArray();
            
            $course->delete();
            
            AuditLog::log('delete', 'Course', $before, null);

            return response()->json(['message' => 'Course deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to delete course: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete course'], 500);
        }
    }
}
