<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class CourseController extends Controller
{
    #[OA\Get(
        path: '/courses',
        summary: 'List all courses',
        description: 'Get paginated list of courses (all authenticated users)',
        security: [['sanctum' => []]],
        tags: ['Courses'],
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
                description: 'Search by title or code',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'show_all',
                in: 'query',
                description: 'Show inactive courses',
                schema: new OA\Schema(type: 'boolean', default: false)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Courses retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
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

    #[OA\Post(
        path: '/courses',
        summary: 'Create course',
        description: 'Create a new course (Instructor/Admin)',
        security: [['sanctum' => []]],
        tags: ['Courses'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'code', 'instructor_id'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Introduction to CS'),
                    new OA\Property(property: 'code', type: 'string', example: 'CS101'),
                    new OA\Property(property: 'instructor_id', type: 'integer', example: 2)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Course created successfully'),
            new OA\Response(response: 403, description: 'Forbidden (Instructor/Admin only)'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
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

    #[OA\Get(
        path: '/courses/{id}',
        summary: 'Get course',
        description: 'Get course by ID with instructor info',
        security: [['sanctum' => []]],
        tags: ['Courses'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Course found'),
            new OA\Response(response: 404, description: 'Course not found')
        ]
    )]
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

    #[OA\Put(
        path: '/courses/{id}',
        summary: 'Update course',
        description: 'Update course information (Instructor/Admin)',
        security: [['sanctum' => []]],
        tags: ['Courses'],
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
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'code', type: 'string'),
                    new OA\Property(property: 'instructor_id', type: 'integer'),
                    new OA\Property(property: 'is_active', type: 'boolean')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Course updated successfully'),
            new OA\Response(response: 404, description: 'Course not found'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
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

    #[OA\Delete(
        path: '/courses/{id}',
        summary: 'Delete course',
        description: 'Delete course by ID (Admin only)',
        security: [['sanctum' => []]],
        tags: ['Courses'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Course deleted successfully'),
            new OA\Response(response: 404, description: 'Course not found')
        ]
    )]
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
