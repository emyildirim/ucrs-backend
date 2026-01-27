<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
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

    public function destroy(Request $request, string $id)
    {
        $enrollment = Enrollment::where('enrollment_id', $id)
            ->where('student_id', $request->user()->user_id)
            ->firstOrFail();

        $before = $enrollment->toArray();
        $enrollment->delete();

        AuditLog::log('delete', 'Enrollment', $before, null);

        return response()->json(['message' => 'Course dropped successfully']);
    }
}
