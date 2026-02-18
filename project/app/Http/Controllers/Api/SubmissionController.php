<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Assignment;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class SubmissionController extends Controller
{
    #[OA\Get(
        path: '/submissions',
        summary: 'List all submissions',
        description: 'Get paginated list of all submissions (Instructor/Admin)',
        security: [['sanctum' => []]],
        tags: ['Submissions'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Submissions retrieved'),
            new OA\Response(response: 403, description: 'Forbidden')
        ]
    )]
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $courseId = $request->input('course_id');

            $query = Submission::with(['assignment.course', 'student', 'grader']);

            if ($courseId) {
                $query->whereHas('assignment', function($q) use ($courseId) {
                    $q->where('course_id', $courseId);
                });
            }

            $submissions = $query->paginate($perPage);

            return response()->json($submissions);
        } catch (\Exception $e) {
            Log::error('Failed to fetch submissions: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch submissions'], 500);
        }
    }

    #[OA\Get(
        path: '/submissions/{id}',
        summary: 'Get submission details',
        description: 'Get specific submission with details (Instructor/Admin)',
        security: [['sanctum' => []]],
        tags: ['Submissions'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Submission details'),
            new OA\Response(response: 404, description: 'Submission not found')
        ]
    )]
    public function show(string $id)
    {
        try {
            $submission = Submission::with(['assignment.course', 'student', 'grader'])->findOrFail($id);
            return response()->json($submission);
        } catch (\Exception $e) {
            Log::error('Failed to fetch submission: ' . $e->getMessage());
            return response()->json(['message' => 'Submission not found'], 404);
        }
    }

    #[OA\Post(
        path: '/assignments/{assignmentId}/submit',
        summary: 'Submit assignment',
        description: 'Create new submission for assignment (Student)',
        security: [['sanctum' => []]],
        tags: ['Submissions'],
        parameters: [
            new OA\Parameter(name: 'assignmentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content_url'],
                properties: [
                    new OA\Property(property: 'content_url', type: 'string', format: 'url', example: 'https://example.com/submission.pdf')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Submission created'),
            new OA\Response(response: 422, description: 'Already submitted or validation error'),
            new OA\Response(response: 404, description: 'Assignment not found')
        ]
    )]
    public function submit(Request $request, string $assignmentId)
    {
        try {
            Assignment::findOrFail($assignmentId);

            $existing = Submission::where('assignment_id', $assignmentId)
                ->where('student_id', $request->user()->user_id)
                ->first();

            if ($existing) {
                return response()->json(['message' => 'Already submitted for this assignment'], 422);
            }

            $validated = $request->validate([
                'content_url' => ['required', 'string', 'url'],
            ]);

            $submission = Submission::create([
                'assignment_id' => $assignmentId,
                'student_id' => $request->user()->user_id,
                'content_url' => $validated['content_url'],
            ]);

            AuditLog::log('create', 'Submission', null, $submission->toArray());

            return response()->json([
                'message' => 'Submission created successfully',
                'data' => $submission->load('assignment')
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create submission: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create submission'], 500);
        }
    }

    #[OA\Put(
        path: '/submissions/{id}',
        summary: 'Update submission',
        description: 'Update submission content (Student, before grading)',
        security: [['sanctum' => []]],
        tags: ['Submissions'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content_url'],
                properties: [
                    new OA\Property(property: 'content_url', type: 'string', format: 'url')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Submission updated'),
            new OA\Response(response: 403, description: 'Cannot update graded submission'),
            new OA\Response(response: 404, description: 'Submission not found')
        ]
    )]
    public function update(Request $request, string $id)
    {
        try {
            $submission = Submission::where('submission_id', $id)
                ->where('student_id', $request->user()->user_id)
                ->whereNull('score')
                ->firstOrFail();

            $before = $submission->toArray();

            $validated = $request->validate([
                'content_url' => ['required', 'string', 'url'],
            ]);

            $submission->update($validated);

            AuditLog::log('update', 'Submission', $before, $submission->fresh()->toArray());

            return response()->json([
                'message' => 'Submission updated successfully',
                'data' => $submission->load('assignment')
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update submission: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update submission. Already graded?'], 500);
        }
    }

    #[OA\Get(
        path: '/submissions/my-submissions',
        summary: 'Get my submissions',
        description: 'Get all submissions for current student',
        security: [['sanctum' => []]],
        tags: ['Submissions'],
        responses: [
            new OA\Response(response: 200, description: 'My submissions retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function mySubmissions(Request $request)
    {
        $submissions = Submission::with(['assignment.course', 'grader'])
            ->where('student_id', $request->user()->user_id)
            ->get();

        return response()->json($submissions);
    }

    #[OA\Put(
        path: '/submissions/{id}/grade',
        summary: 'Grade submission',
        description: 'Assign grade to submission (Instructor/Admin)',
        security: [['sanctum' => []]],
        tags: ['Submissions'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['score'],
                properties: [
                    new OA\Property(property: 'score', type: 'number', format: 'float', example: 95)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Submission graded'),
            new OA\Response(response: 404, description: 'Submission not found'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function grade(Request $request, string $id)
    {
        try {
            $submission = Submission::findOrFail($id);
            $before = $submission->toArray();

            $validated = $request->validate([
                'score' => ['required', 'integer', 'min:0'],
            ]);

            $submission->update([
                'score' => $validated['score'],
                'graded_by' => $request->user()->user_id,
            ]);

            AuditLog::log('grade', 'Submission', $before, $submission->fresh()->toArray());

            return response()->json([
                'message' => 'Submission graded successfully',
                'data' => $submission->load(['student', 'grader'])
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to grade submission: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to grade submission'], 500);
        }
    }
}
