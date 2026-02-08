<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Assignment;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubmissionController extends Controller
{
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

    public function mySubmissions(Request $request)
    {
        $submissions = Submission::with(['assignment.course', 'grader'])
            ->where('student_id', $request->user()->user_id)
            ->get();

        return response()->json($submissions);
    }

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
