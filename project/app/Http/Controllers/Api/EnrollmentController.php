<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class EnrollmentController extends Controller
{
    #[OA\Get(
        path: '/enrollments',
        summary: 'List all enrollments',
        description: 'Get paginated list of all enrollments (Admin only)',
        security: [['sanctum' => []]],
        tags: ['Enrollments'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Enrollments retrieved'),
            new OA\Response(response: 403, description: 'Forbidden (Admin only)')
        ]
    )]
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

    #[OA\Post(
        path: '/courses/{courseId}/enroll',
        summary: 'Enroll in course',
        description: 'Enroll current student in a course',
        security: [['sanctum' => []]],
        tags: ['Enrollments'],
        parameters: [
            new OA\Parameter(name: 'courseId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 201, description: 'Enrolled successfully'),
            new OA\Response(response: 422, description: 'Already enrolled'),
            new OA\Response(response: 404, description: 'Course not found')
        ]
    )]
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

    #[OA\Get(
        path: '/enrollments/my-courses',
        summary: 'Get my enrolled courses',
        description: 'Get courses that current student is enrolled in',
        security: [['sanctum' => []]],
        tags: ['Enrollments'],
        responses: [
            new OA\Response(response: 200, description: 'My courses retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function myCourses(Request $request)
    {
        $enrollments = Enrollment::with('course.instructor')
            ->where('student_id', $request->user()->user_id)
            ->get();

        return response()->json($enrollments);
    }

    #[OA\Put(
        path: '/enrollments/{id}',
        summary: 'Update enrollment',
        description: 'Update enrollment final grade (Admin only)',
        security: [['sanctum' => []]],
        tags: ['Enrollments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['final_grade'],
                properties: [
                    new OA\Property(property: 'final_grade', type: 'number', format: 'float', example: 85.5)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Grade updated'),
            new OA\Response(response: 404, description: 'Enrollment not found'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
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

    #[OA\Delete(
        path: '/enrollments/{id}',
        summary: 'Drop course',
        description: 'Student drops a course enrollment',
        security: [['sanctum' => []]],
        tags: ['Enrollments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Enrollment deleted'),
            new OA\Response(response: 403, description: 'Not your enrollment'),
            new OA\Response(response: 404, description: 'Enrollment not found')
        ]
    )]
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
