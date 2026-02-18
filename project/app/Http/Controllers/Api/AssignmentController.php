<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class AssignmentController extends Controller
{
    #[OA\Get(
        path: '/courses/{courseId}/assignments',
        summary: 'List course assignments',
        description: 'Get all assignments for a specific course',
        security: [['sanctum' => []]],
        tags: ['Assignments'],
        parameters: [
            new OA\Parameter(name: 'courseId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Assignments retrieved'),
            new OA\Response(response: 404, description: 'Course not found')
        ]
    )]
    public function index(string $courseId)
    {
        $assignments = Assignment::where('course_id', $courseId)
            ->orderBy('due_at', 'asc')
            ->get();

        return response()->json($assignments);
    }

    #[OA\Post(
        path: '/courses/{courseId}/assignments',
        summary: 'Create assignment',
        description: 'Create new assignment for a course (Instructor/Admin)',
        security: [['sanctum' => []]],
        tags: ['Assignments'],
        parameters: [
            new OA\Parameter(name: 'courseId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'description', 'due_at', 'max_points'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Homework 1'),
                    new OA\Property(property: 'description', type: 'string', example: 'Complete exercises 1-10'),
                    new OA\Property(property: 'due_at', type: 'string', format: 'date-time', example: '2026-02-01 23:59:59'),
                    new OA\Property(property: 'max_points', type: 'integer', example: 100)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Assignment created'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 403, description: 'Forbidden')
        ]
    )]
    public function store(Request $request, string $courseId)
    {
        Course::findOrFail($courseId);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'due_at' => ['required', 'date'],
            'max_points' => ['required', 'integer', 'min:1'],
        ]);

        $assignment = Assignment::create([
            ...$validated,
            'course_id' => $courseId,
        ]);

        AuditLog::log('create', 'Assignment', null, $assignment->toArray());

        return response()->json($assignment, 201);
    }

    #[OA\Get(
        path: '/assignments/{id}',
        summary: 'Get assignment details',
        description: 'Get specific assignment with submissions',
        security: [['sanctum' => []]],
        tags: ['Assignments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Assignment details'),
            new OA\Response(response: 404, description: 'Assignment not found')
        ]
    )]
    public function show(string $id)
    {
        $assignment = Assignment::with(['course', 'submissions'])->findOrFail($id);
        return response()->json($assignment);
    }

    #[OA\Put(
        path: '/assignments/{id}',
        summary: 'Update assignment',
        description: 'Update assignment details (Instructor/Admin)',
        security: [['sanctum' => []]],
        tags: ['Assignments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'due_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'max_points', type: 'integer')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Assignment updated'),
            new OA\Response(response: 404, description: 'Assignment not found'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function update(Request $request, string $id)
    {
        try {
            $assignment = Assignment::findOrFail($id);
            $before = $assignment->toArray();

            $validated = $request->validate([
                'title' => ['sometimes', 'string', 'max:255'],
                'description' => ['sometimes', 'string'],
                'due_at' => ['sometimes', 'date'],
                'max_points' => ['sometimes', 'integer', 'min:1'],
            ]);

            $assignment->update($validated);

            AuditLog::log('update', 'Assignment', $before, $assignment->fresh()->toArray());

            return response()->json([
                'message' => 'Assignment updated successfully',
                'data' => $assignment
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update assignment: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update assignment'], 500);
        }
    }

    #[OA\Delete(
        path: '/assignments/{id}',
        summary: 'Delete assignment',
        description: 'Delete assignment (Instructor/Admin)',
        security: [['sanctum' => []]],
        tags: ['Assignments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Assignment deleted'),
            new OA\Response(response: 404, description: 'Assignment not found')
        ]
    )]
    public function destroy(string $id)
    {
        try {
            $assignment = Assignment::findOrFail($id);
            $before = $assignment->toArray();

            $assignment->delete();

            AuditLog::log('delete', 'Assignment', $before, null);

            return response()->json(['message' => 'Assignment deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to delete assignment: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete assignment'], 500);
        }
    }
}
