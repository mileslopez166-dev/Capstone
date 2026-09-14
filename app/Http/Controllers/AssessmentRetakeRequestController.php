<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentRetakeRequest;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssessmentRetakeRequestController extends Controller
{
    public function store(Request $request, Assessment $assessment): RedirectResponse
    {
        $student = $request->user();

        abort_unless($student?->isStudent(), 403);
        abort_unless($assessment->status === 'published', 404);

        $hasSubmitted = AssessmentSubmission::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $student->id)
            ->exists();

        abort_unless($hasSubmitted, 422);

        $validated = $request->validate([
            'requested_tries' => ['required', 'integer', 'min:1', 'max:10'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        AssessmentRetakeRequest::query()->updateOrCreate(
            [
                'assessment_id' => $assessment->id,
                'user_id' => $student->id,
                'status' => 'pending',
            ],
            [
                'requested_tries' => $validated['requested_tries'],
                'message' => $validated['message'] ?? null,
                'teacher_id' => $assessment->created_by,
                'approved_tries' => 0,
                'remaining_tries' => 0,
                'decided_at' => null,
            ]
        );

        return redirect()
            ->route('student.dashboard')
            ->with('status', 'Token has been requested for retake. Please wait for your teacher approval.');
    }

    public function approve(Request $request, User $student, AssessmentRetakeRequest $retakeRequest): RedirectResponse
    {
        $teacher = $request->user();

        abort_unless($teacher?->isTeacher(), 403);
        abort_unless($student->isStudent() && $retakeRequest->user_id === $student->id, 404);
        abort_unless($retakeRequest->assessment?->created_by === $teacher->id, 404);

        $validated = $request->validate([
            'approved_tries' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $retakeRequest->update([
            'teacher_id' => $teacher->id,
            'status' => 'approved',
            'approved_tries' => $validated['approved_tries'],
            'remaining_tries' => $validated['approved_tries'],
            'decided_at' => now(),
        ]);

        return back()->with('status', "Approved {$student->name}'s retake token.");
    }

    public function decline(Request $request, User $student, AssessmentRetakeRequest $retakeRequest): RedirectResponse
    {
        $teacher = $request->user();

        abort_unless($teacher?->isTeacher(), 403);
        abort_unless($student->isStudent() && $retakeRequest->user_id === $student->id, 404);
        abort_unless($retakeRequest->assessment?->created_by === $teacher->id, 404);

        $retakeRequest->update([
            'teacher_id' => $teacher->id,
            'status' => 'declined',
            'approved_tries' => 0,
            'remaining_tries' => 0,
            'decided_at' => now(),
        ]);

        return back()->with('status', "Declined {$student->name}'s retake request.");
    }
}