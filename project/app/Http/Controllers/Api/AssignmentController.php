<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssignmentController extends Controller
{
    public function index(string $courseId)
    {
        $assignments = Assignment::where('course_id', $courseId)
            ->orderBy('due_at', 'asc')
            ->get();

        return response()->json($assignments);
    }

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

    public function show(string $id)
    {
        $assignment = Assignment::with(['course', 'submissions'])->findOrFail($id);
        return response()->json($assignment);
    }

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
