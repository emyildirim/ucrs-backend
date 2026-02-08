<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');

            $query = Enrollment::with(['course', 'student']);

            if ($search) {
                $query->whereHas('student', function($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%");
                })->orWhereHas('course', function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                });
            }

            $enrollments = $query->paginate($perPage);

            return response()->json($enrollments);
        } catch (\Exception $e) {
            Log::error('Failed to fetch enrollments: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch enrollments'], 500);
        }
    }

    public function enroll(Request $request, string $courseId)
    {
        $course = Course::where('course_id', $courseId)->active()->firstOrFail();
        
        $existing = Enrollment::where('course_id', $courseId)
            ->where('student_id', $request->user()->user_id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Already enrolled in this course'], 422);
        }

        $enrollment = Enrollment::create([
            'course_id' => $courseId,
            'student_id' => $request->user()->user_id,
        ]);

        AuditLog::log('create', 'Enrollment', null, $enrollment->toArray());

        return response()->json($enrollment->load('course'), 201);
    }

    public function myCourses(Request $request)
    {
        $enrollments = Enrollment::with('course.instructor')
            ->where('student_id', $request->user()->user_id)
            ->get();

        return response()->json($enrollments);
    }

    public function update(Request $request, string $id)
    {
        try {
            $enrollment = Enrollment::findOrFail($id);
            $before = $enrollment->toArray();

            $validated = $request->validate([
                'final_grade' => ['required', 'numeric', 'min:0', 'max:100'],
            ]);

            $enrollment->update($validated);

            AuditLog::log('update', 'Enrollment', $before, $enrollment->fresh()->toArray());

            return response()->json([
                'message' => 'Grade updated successfully',
                'data' => $enrollment->load(['course', 'student'])
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update enrollment: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update enrollment'], 500);
        }
    }

    public function destroy(Request $request, string $id)
    {
        try {
            $enrollment = Enrollment::where('enrollment_id', $id)
                ->where('student_id', $request->user()->user_id)
                ->firstOrFail();

            $before = $enrollment->toArray();
            $enrollment->delete();

            AuditLog::log('delete', 'Enrollment', $before, null);

            return response()->json(['message' => 'Course dropped successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to delete enrollment: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete enrollment'], 500);
        }
    }
}
