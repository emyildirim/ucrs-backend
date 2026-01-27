<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Assignment;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function submit(Request $request, string $assignmentId)
    {
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

        return response()->json($submission->load('assignment'), 201);
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

        return response()->json($submission->load(['student', 'grader']));
    }
}
