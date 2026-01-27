<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::with('instructor')->active()->get();
        return response()->json($courses);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'unique:courses,code'],
            'instructor_id' => ['required', 'exists:users,user_id'],
        ]);

        $course = Course::create($validated);
        
        AuditLog::log('create', 'Course', null, $course->toArray());

        return response()->json($course->load('instructor'), 201);
    }

    public function show(string $id)
    {
        $course = Course::with(['instructor', 'enrollments.student', 'assignments'])->findOrFail($id);
        return response()->json($course);
    }

    public function update(Request $request, string $id)
    {
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

        return response()->json($course->load('instructor'));
    }

    public function destroy(string $id)
    {
        $course = Course::findOrFail($id);
        $before = $course->toArray();
        
        $course->delete();
        
        AuditLog::log('delete', 'Course', $before, null);

        return response()->json(['message' => 'Course deleted successfully']);
    }
}
