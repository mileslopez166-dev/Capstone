<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\AssessmentRetakeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isTeacher(), 403);

        $assessments = Assessment::query()
            ->where('created_by', $request->user()->id)
            ->latest()
            ->get();

        return view('assessments.index', [
            'assessments' => $assessments,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isTeacher(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'in:literacy,numeracy'],
            'quiz_type' => ['required', Rule::in(['multiple_choice', 'data_egg', 'flashcards'])],
            'delivery_method' => ['required', Rule::in(['manual'])],
            'target_section' => ['required', Rule::in(['all', 'section_a', 'section_b', 'section_c'])],
            'assessment_type' => ['required', Rule::in(['silent_reading', 'oral_reading', 'listening_comprehension', 'group_screening'])],
            'focus_areas' => ['required', 'array', 'min:1'],
            'focus_areas.*' => ['string', Rule::in([
                'Reading Fluency',
                'Comprehension Depth',
                'Spelling & Vocabulary',
                'Mental Arithmetic',
                'Problem Solving',
                'Data Interpretation',
            ])],
            'manual_questions' => ['required_unless:assessment_type,oral_reading', 'nullable', 'array', 'min:1'],
            'manual_questions.*.question' => ['required_unless:assessment_type,oral_reading', 'nullable', 'string', 'max:1000'],
            'manual_questions.*.answers.A' => ['required_unless:assessment_type,oral_reading', 'nullable', 'string', 'max:255'],
            'manual_questions.*.answers.B' => ['required_unless:assessment_type,oral_reading', 'nullable', 'string', 'max:255'],
            'manual_questions.*.answers.C' => ['required_unless:assessment_type,oral_reading', 'nullable', 'string', 'max:255'],
            'manual_questions.*.answers.D' => ['required_unless:assessment_type,oral_reading', 'nullable', 'string', 'max:255'],
            'manual_questions.*.correct_answer' => ['required_unless:assessment_type,oral_reading', 'nullable', Rule::in(['A', 'B', 'C', 'D'])],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'story_title' => ['nullable', 'string', 'max:255'],
            'story_description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $manualQuestions = ($validated['assessment_type'] ?? null) === 'oral_reading'
            ? []
            : collect($validated['manual_questions'] ?? [])
            ->values()
            ->map(fn (array $question): array => [
                'question' => $question['question'] ?? '',
                'answers' => [
                    'A' => $question['answers']['A'] ?? '',
                    'B' => $question['answers']['B'] ?? '',
                    'C' => $question['answers']['C'] ?? '',
                    'D' => $question['answers']['D'] ?? '',
                ],
                'correct_answer' => $question['correct_answer'] ?? null,
            ])
            ->all();

        $request->user()->createdAssessments()->create([
            'title' => $validated['title'],
            'subject' => $validated['subject'],
            'quiz_type' => $validated['quiz_type'],
            'delivery_method' => 'manual',
            'target_section' => $validated['target_section'],
            'assessment_type' => $validated['assessment_type'],
            'focus_areas' => $validated['focus_areas'],
            'asset_path' => null,
            'manual_questions' => $manualQuestions,
            'instructions' => $validated['instructions'] ?? null,
            'story_title' => $validated['story_title'] ?? null,
            'story_description' => $validated['story_description'] ?? null,
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('assessments.index')
            ->with('status', 'Assessment created successfully.');
    }

    public function show(Request $request, Assessment $assessment): View
    {
        abort_unless($request->user()?->isTeacher(), 403);
        abort_unless($assessment->created_by === $request->user()->id, 404);

        return view('assessments.show', [
            'assessment' => $assessment,
        ]);
    }

    public function updateAvailability(Request $request, Assessment $assessment): RedirectResponse
    {
        abort_unless($request->user()?->isTeacher(), 403);
        abort_unless($assessment->created_by === $request->user()->id, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['draft', 'published'])],
        ]);

        $assessment->update([
            'status' => $validated['status'],
        ]);

        $message = $validated['status'] === 'published'
            ? 'Assessment unlocked and visible to students.'
            : 'Assessment locked. Students can no longer answer it.';

        return back()->with('status', $message);
    }

    public function destroy(Request $request, Assessment $assessment): RedirectResponse
    {
        abort_unless($request->user()?->isTeacher(), 403);
        abort_unless($assessment->created_by === $request->user()->id, 404);

        if ($assessment->asset_path) {
            Storage::disk('public')->delete($assessment->asset_path);
        }

        $assessment->delete();

        return redirect()
            ->route('assessments.index')
            ->with('status', 'Assessment deleted successfully.');
    }

    public function submitStudentAttempt(Request $request, Assessment $assessment): JsonResponse
    {
        abort_unless($request->user()?->isStudent(), 403);
        abort_unless($assessment->status === 'published', 404);

        $targetSection = $request->user()->section ? strtolower(str_replace(' ', '_', $request->user()->section)) : null;
        abort_unless(in_array($assessment->target_section, ['all', null], true) || ($targetSection && $assessment->target_section === $targetSection), 404);

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
        ]);

        $manualQuestions = collect($assessment->manual_questions ?? [])->values();
        $questionCount = $manualQuestions->count();
        $validIndexes = range(0, max(0, $questionCount - 1));
        $answers = collect($validated['answers'])
            ->mapWithKeys(fn ($answer, $index): array => [(int) $index => $answer])
            ->only($validIndexes);

        $correctCount = $manualQuestions
            ->filter(fn (array $question, int $index): bool => ($question['correct_answer'] ?? null) === $answers->get($index))
            ->count();

        $pointsPerQuestion = 250;
        $points = $correctCount * $pointsPerQuestion;
        $possiblePoints = $questionCount * $pointsPerQuestion;
        $studentId = $request->user()->id;
        $previousAttempts = AssessmentSubmission::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $studentId)
            ->count();
        $retakeToken = null;

        if ($previousAttempts > 0) {
            $retakeToken = AssessmentRetakeRequest::query()
                ->where('assessment_id', $assessment->id)
                ->where('user_id', $studentId)
                ->where('status', 'approved')
                ->where('remaining_tries', '>', 0)
                ->oldest('decided_at')
                ->first();

            abort_unless($retakeToken, 403, 'A teacher-approved retake token is required.');
        }

        $submission = AssessmentSubmission::query()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $studentId,
            'attempt_number' => $previousAttempts + 1,
            'answers' => $answers->all(),
            'correct_count' => $correctCount,
            'question_count' => $questionCount,
            'points' => $points,
            'possible_points' => $possiblePoints,
            'submitted_at' => now(),
        ]);

        if ($retakeToken) {
            $retakeToken->decrement('remaining_tries');
        }

        return response()->json([
            'attempt_number' => $submission->attempt_number,
            'correct_count' => $correctCount,
            'question_count' => $questionCount,
            'points' => $points,
            'possible_points' => $possiblePoints,
            'accuracy' => $questionCount > 0 ? round(($correctCount / $questionCount) * 100) : 0,
        ]);
    }
}
