<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentProgress;
use App\Models\AssessmentRetakeRequest;
use App\Models\AssessmentSubmission;
use App\Models\User;
use App\Support\NotificationSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'retry_limit' => $this->retryLimitRules(false),
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

        $assessment = $request->user()->createdAssessments()->create([
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
            'retry_limit' => Assessment::normalizeRetryLimit($validated['retry_limit'] ?? 0),
        ]);

        if ($assessment->status === 'published') {
            NotificationSender::notifyAssessmentPublished($assessment);
        }

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

        $wasPublished = $assessment->status === 'published';

        $assessment->update([
            'status' => $validated['status'],
        ]);

        if (! $wasPublished && $assessment->status === 'published') {
            NotificationSender::notifyAssessmentPublished($assessment);
        }

        $message = $validated['status'] === 'published'
            ? 'Assessment unlocked and visible to students.'
            : 'Assessment locked. Students can no longer answer it.';

        return back()->with('status', $message);
    }

    public function updateRetries(Request $request, Assessment $assessment): RedirectResponse
    {
        abort_unless($request->user()?->isTeacher(), 403);
        abort_unless($assessment->created_by === $request->user()->id, 404);
        $validated = $request->validate([
            'retry_limit' => $this->retryLimitRules(),
        ]);

        $assessment->update([
            'retry_limit' => Assessment::normalizeRetryLimit($validated['retry_limit']),
        ]);

        return back()->with('status', 'Assessment retry limit updated.');
    }

    private function retryLimitRules(bool $required = true): array
    {
        return array_merge($required ? ['required'] : ['sometimes', 'required'], [
            function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === 'unlimited') {
                    return;
                }

                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $fail('Choose a retry limit from 0 to 10, or select unlimited.');

                    return;
                }

                $retryLimit = (int) $value;

                if ($retryLimit < 0 || $retryLimit > 10) {
                    $fail('Choose a retry limit from 0 to 10, or select unlimited.');
                }
            },
        ]);
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

    private function authorizeStudentAssessment(Request $request, Assessment $assessment): void
    {
        abort_unless($request->user()?->isStudent(), 403);
        abort_unless($assessment->status === 'published', 404);

        $targetSection = $request->user()->section ? strtolower(str_replace(' ', '_', $request->user()->section)) : null;
        abort_unless(in_array($assessment->target_section, ['all', null], true) || ($targetSection && $assessment->target_section === $targetSection), 404);
    }

    public function showStudent(Request $request, Assessment $assessment): View|RedirectResponse
    {
        $this->authorizeStudentAssessment($request, $assessment);

        return DB::transaction(function () use ($request, $assessment) {
            $student = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            $attemptCount = $assessment->submissions()->where('user_id', $student->id)->count();
            $remainingTokens = AssessmentRetakeRequest::query()
                ->where('assessment_id', $assessment->id)->where('user_id', $student->id)
                ->where('status', 'approved')->sum('remaining_tries');

            if ($assessment->remainingIncludedAttempts($attemptCount) === 0 && $remainingTokens <= 0) {
                AssessmentRetakeRequest::query()->firstOrCreate([
                    'assessment_id' => $assessment->id, 'user_id' => $student->id, 'status' => 'pending',
                ], [
                    'teacher_id' => $assessment->created_by, 'requested_tries' => 1,
                    'approved_tries' => 0, 'remaining_tries' => 0,
                    'message' => 'Student requested a retake token from the assessment page.',
                ]);

                return redirect()->route('student.dashboard')
                    ->with('status', 'Token has been requested for retake. Please wait for your teacher approval.');
            }

            $progress = AssessmentProgress::forAttempt($assessment, $student, $attemptCount + 1);

            return view('student.assessment', compact('assessment', 'progress'));
        });
    }

    private function progressRules(): array
    {
        return [
            'state' => ['sometimes', 'array:answers,phase,reading_seconds,timer_status,word_marks,sentence_marks,mark_mode,scroll_ratio'],
            'state.answers' => ['present_with:state', 'array'],
            'state.answers.*' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
            'state.phase' => ['required_with:state', Rule::in(['reading', 'questions', 'finished'])],
            'state.reading_seconds' => ['required_with:state', 'integer', 'min:0', 'max:604800'],
            'state.timer_status' => ['required_with:state', Rule::in(['idle', 'running', 'paused', 'finished'])],
            'state.word_marks' => ['sometimes', 'array', 'max:10000'],
            'state.word_marks.*' => ['integer', 'between:0,2'],
            'state.sentence_marks' => ['sometimes', 'array', 'max:10000'],
            'state.sentence_marks.*' => ['integer', 'between:0,2'],
            'state.mark_mode' => ['sometimes', Rule::in(['word', 'sentence-1', 'sentence-2'])],
            'state.scroll_ratio' => ['sometimes', 'numeric', 'between:0,1'],
            'revision' => ['required_with:state', 'integer', 'min:1', 'max:9007199254740991'],
        ];
    }

    private function validateAnswerIndexes(array $answers, Assessment $assessment): void
    {
        $questionCount = count($assessment->manual_questions ?? []);
        foreach (array_keys($answers) as $index) {
            abort_unless(ctype_digit((string) $index) && (int) $index < $questionCount, 422, 'Invalid question index.');
        }
    }

    public function saveStudentProgress(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorizeStudentAssessment($request, $assessment);
        $validated = $request->validate(array_merge($this->progressRules(), [
            'attempt_key' => ['required', 'uuid'],
            'state' => ['required', $this->progressRules()['state'][1]],
        ]));
        $this->validateAnswerIndexes($validated['state']['answers'], $assessment);

        return DB::transaction(function () use ($request, $assessment, $validated) {
            User::query()->lockForUpdate()->findOrFail($request->user()->id);
            $progress = AssessmentProgress::query()
                ->where('assessment_id', $assessment->id)->where('user_id', $request->user()->id)
                ->where('attempt_key', $validated['attempt_key'])->firstOrFail();
            abort_if($progress->submission_id !== null, 409, 'This attempt has already been submitted.');

            // Delayed autosaves must not overwrite a newer snapshot.
            if ($validated['revision'] > $progress->revision) {
                $progress->update(['state' => $validated['state'], 'revision' => $validated['revision']]);
            }

            return response()->json(['revision' => $progress->revision]);
        });
    }

    public function submitStudentAttempt(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorizeStudentAssessment($request, $assessment);

        $validated = $request->validate(array_merge($this->progressRules(), [
            'attempt_key' => ['sometimes', 'required', 'uuid'],
            'answers' => ['present', 'array', 'size:'.count($assessment->manual_questions ?? [])],
            'answers.*' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
        ]));
        $this->validateAnswerIndexes($validated['answers'], $assessment);

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
        $submission = DB::transaction(function () use ($assessment, $validated, $studentId, $answers, $correctCount, $questionCount, $points, $possiblePoints) {
            // Serialize attempts and token consumption for this student, including multiple tabs.
            $student = User::query()->lockForUpdate()->findOrFail($studentId);
            $progress = isset($validated['attempt_key']) ? AssessmentProgress::query()
                ->where('assessment_id', $assessment->id)->where('user_id', $studentId)
                ->where('attempt_key', $validated['attempt_key'])->firstOrFail() : null;
            if ($progress?->submission_id) {
                return $progress->submission;
            }
            $previousAttempts = AssessmentSubmission::query()
                ->where('assessment_id', $assessment->id)
                ->where('user_id', $studentId)
                ->count();
            $retakeToken = null;

            if ($assessment->remainingIncludedAttempts($previousAttempts) === 0) {
                $retakeToken = AssessmentRetakeRequest::query()
                    ->where('assessment_id', $assessment->id)
                    ->where('user_id', $studentId)
                    ->where('status', 'approved')
                    ->where('remaining_tries', '>', 0)
                    ->oldest('decided_at')
                    ->lockForUpdate()
                    ->first();

                abort_unless($retakeToken, 403, 'A teacher-approved retake token is required.');
            }

            $progress ??= AssessmentProgress::forAttempt($assessment, $student, $previousAttempts + 1);
            abort_unless($progress->attempt_number === $previousAttempts + 1, 409, 'This attempt is no longer current.');

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

            $state = $validated['state'] ?? $progress->state ?? [];
            $state['answers'] = $answers->all();
            $state['phase'] = 'finished';
            $progress->update(['submission_id' => $submission->id, 'state' => $state]);

            return $submission;
        });

        if ($submission->wasRecentlyCreated) {
            NotificationSender::notifyAssessmentCompleted($submission->load(['assessment.teacher', 'student']));
        }

        return response()->json([
            'attempt_number' => $submission->attempt_number,
            'correct_count' => $submission->correct_count,
            'question_count' => $submission->question_count,
            'points' => $submission->points,
            'possible_points' => $submission->possible_points,
            'accuracy' => $submission->question_count > 0 ? round(($submission->correct_count / $submission->question_count) * 100) : 0,
        ]);
    }
}
