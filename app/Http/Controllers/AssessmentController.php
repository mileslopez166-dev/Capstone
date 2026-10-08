<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentProgress;
use App\Models\AssessmentRetakeRequest;
use App\Models\AssessmentSubmission;
use App\Models\User;
use App\Support\AdaptiveQuestions;
use App\Support\AssessmentCoinRewards;
use App\Support\AssessmentParticipant;
use App\Support\AssessmentScores;
use App\Support\MLPredictionService;
use App\Support\NotificationSender;
use App\Support\PhilIri;
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
        $filters = $request->validate([
            'subject' => ['nullable', Rule::in(['literacy', 'numeracy'])],
            'q' => ['nullable', 'string', 'max:255'],
        ]);
        $subject = $filters['subject'] ?? null;
        $search = trim($filters['q'] ?? '');

        $assessments = Assessment::query()
            ->where('created_by', $request->user()->id)
            ->when($subject, fn ($query) => $query->where('subject', $subject))
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->latest()
            ->paginate(12)->withQueryString();

        return view('assessments.index', compact('assessments', 'subject', 'search'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        abort_unless($request->user()?->isTeacher(), 403);
        if ($request->query('subject') === 'numeracy') {
            return redirect()->route('worksheets.index');
        }

        return view('assessments.create', [
            'storyTemplates' => json_decode(file_get_contents(resource_path('data/literacy-stories.json')), true, 512, JSON_THROW_ON_ERROR),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isTeacher(), 403);
        if ($request->input('subject') === 'numeracy') {
            return redirect()->route('worksheets.index');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'in:literacy,numeracy'],
            'quiz_type' => ['required', Rule::in(['multiple_choice', 'data_egg', 'flashcards', 'treasure_quest'])],
            'question_selection' => ['sometimes', Rule::in(['fixed', 'automatic'])],
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
            'manual_questions.*.difficulty' => ['nullable', Rule::in(['frustration', 'instructional', 'independent', 'advanced'])],
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
                    ...(! empty($question['difficulty']) ? ['difficulty' => $question['difficulty']] : []),
                ])
                ->all();

        $selection = $validated['question_selection'] ?? 'fixed';
        if ($validated['assessment_type'] === 'oral_reading') {
            $selection = 'fixed';
        }
        if ($selection === 'automatic') {
            if (! in_array($validated['assessment_type'], ['silent_reading', 'listening_comprehension'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['question_selection' => 'Automatic question selection is available for silent reading and listening comprehension.']);
            }
            AdaptiveQuestions::validateBank($manualQuestions);
        }

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
            'question_selection' => $selection,
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

        $eligibleStudents = User::where('role', 'student')->where('approval_status', 'approved')->orderBy('name')->get()
            ->filter(fn (User $student) => AssessmentParticipant::matchesSection($assessment, $student));

        if ($assessment->worksheet_number) {
            return view('worksheets.assignment', ['assessment' => $assessment, 'eligibleStudents' => $eligibleStudents,
                'worksheet' => \App\Support\NumeracyWorksheets::find($assessment->worksheet_number)]);
        }

        return view('assessments.show', [
            'assessment' => $assessment,
            'eligibleStudents' => $eligibleStudents,
        ]);
    }

    public function startForStudent(Request $request, Assessment $assessment): RedirectResponse
    {
        abort_unless($request->user()?->isTeacher() && $request->user()->isApproved(), 403);
        abort_unless($assessment->created_by === $request->user()->id, 404);
        $data = $request->validate(['student_id' => ['required', 'integer']]);
        $student = AssessmentParticipant::resolve($request, $assessment, User::findOrFail($data['student_id']));

        return redirect()->route('teacher.assessments.take', [$assessment, $student]);
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

    public function showStudent(Request $request, Assessment $assessment, ?User $student = null): View|RedirectResponse
    {
        $student = AssessmentParticipant::resolve($request, $assessment, $student);
        $assisted = $request->user()->isTeacher();

        $pendingWorksheet = $assessment->worksheetAttempts()->where('user_id', $student->id)->whereNull('reviewed_at')->first();
        if ($pendingWorksheet) {
            return redirect()->route('worksheets.review', $pendingWorksheet);
        }

        return DB::transaction(function () use ($student, $assisted, $assessment) {
            $student = User::query()->lockForUpdate()->findOrFail($student->id);
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
                    'message' => $assisted ? 'Retake requested during a teacher-assisted assessment.' : 'Student requested a retake token from the assessment page.',
                ]);

                if ($assisted) {
                    return redirect()->route('students.show', $student)
                        ->with('status', 'No tries remain for '.$student->name.'. Approve the retake request below before continuing.');
                }

                return redirect()->route('student.dashboard')
                    ->with('status', 'Token has been requested for retake. Please wait for your teacher approval.');
            }

            $progress = AssessmentProgress::forAttempt($assessment, $student, $attemptCount + 1);

            if ($assessment->worksheet_number) {
                return view('worksheets.answer', compact('assessment', 'progress', 'student', 'assisted') + [
                    'worksheet' => \App\Support\NumeracyWorksheets::find($assessment->worksheet_number),
                ]);
            }

            return view('student.assessment', compact('assessment', 'progress', 'student', 'assisted') + [
                'attemptQuestions' => $progress->question_snapshot,
            ]);
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

    private function validateAnswerIndexes(array $answers, array $questions): void
    {
        $questionCount = count($questions);
        foreach (array_keys($answers) as $index) {
            abort_unless(ctype_digit((string) $index) && (string) (int) $index === (string) $index && (int) $index < $questionCount, 422, 'Invalid question index.');
        }
    }

    public function saveStudentProgress(Request $request, Assessment $assessment, ?User $student = null): JsonResponse
    {
        $student = AssessmentParticipant::resolve($request, $assessment, $student);
        abort_if($assessment->worksheet_number, 422, 'Use the worksheet progress endpoint.');
        $validated = $request->validate(array_merge($this->progressRules(), [
            'attempt_key' => ['required', 'uuid'],
            'state' => ['required', $this->progressRules()['state'][1]],
        ]));

        return DB::transaction(function () use ($request, $student, $assessment, $validated) {
            User::query()->lockForUpdate()->findOrFail($student->id);
            $progress = AssessmentProgress::query()
                ->where('assessment_id', $assessment->id)->where('user_id', $student->id)
                ->where('attempt_key', $validated['attempt_key'])->firstOrFail();
            abort_if($progress->submission_id !== null, 409, 'This attempt has already been submitted.');
            $progress = AdaptiveQuestions::freeze($progress, $assessment, $student);
            $this->validateAnswerIndexes($validated['state']['answers'], $progress->question_snapshot ?? []);

            // Delayed autosaves must not overwrite a newer snapshot.
            if ($validated['revision'] > $progress->revision) {
                $progress->update(['state' => $validated['state'], 'revision' => $validated['revision']]);
                $progress->recordAssistance($request->user());
            }

            return response()->json(['revision' => $progress->revision]);
        });
    }

    public function submitStudentAttempt(Request $request, Assessment $assessment, ?User $student = null): JsonResponse
    {
        $student = AssessmentParticipant::resolve($request, $assessment, $student);
        abort_if($assessment->worksheet_number, 422, 'This worksheet requires teacher review.');

        $validated = $request->validate(array_merge($this->progressRules(), [
            'attempt_key' => [$request->user()->isTeacher() || $assessment->question_selection === 'automatic' ? 'required' : 'sometimes', 'required', 'uuid'],
            'answers' => ['present', 'array', 'max:1000'],
            'answers.*' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
        ]));
        $studentId = $student->id;
        $submission = DB::transaction(function () use ($request, $assessment, $validated, $studentId) {
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
            $progress = AdaptiveQuestions::freeze($progress, $assessment, $student);
            $questions = $progress->question_snapshot ?? [];
            $this->validateAnswerIndexes($validated['answers'], $questions);
            if (isset($validated['state']['answers'])) {
                $this->validateAnswerIndexes($validated['state']['answers'], $questions);
            }
            $questionCount = count($questions);
            if (count($validated['answers']) !== $questionCount) {
                throw \Illuminate\Validation\ValidationException::withMessages(['answers' => 'Answer every question in this attempt before submitting.']);
            }
            $answers = collect($validated['answers'])->mapWithKeys(fn ($answer, $index) => [(int) $index => $answer]);
            $correctCount = collect($questions)->filter(fn ($question, $index) => ($question['correct_answer'] ?? null) === $answers->get($index))->count();
            $pointsPerCorrectAnswer = (int) config('gamification.points_per_correct_answer');
            $points = $correctCount * $pointsPerCorrectAnswer;
            $possiblePoints = $questionCount * $pointsPerCorrectAnswer;

            $state = $validated['state'] ?? $progress->state ?? [];
            $submission = AssessmentSubmission::query()->create([
                'assessment_id' => $assessment->id,
                'user_id' => $studentId,
                'attempt_number' => $previousAttempts + 1,
                'answers' => $answers->all(),
                'question_snapshot' => $questions,
                'selection_context' => $progress->selection_context,
                'correct_count' => $correctCount,
                'question_count' => $questionCount,
                'points' => $points,
                'possible_points' => $possiblePoints,
                'phil_iri' => PhilIri::initial($assessment, $correctCount, $questionCount, $state),
                'submitted_at' => now(),
            ]);

            if ($retakeToken) {
                $retakeToken->decrement('remaining_tries');
            }

            $state['answers'] = $answers->all();
            $state['phase'] = 'finished';
            $progress->update(['submission_id' => $submission->id, 'state' => $state]);
            $progress->recordAssistance($request->user());

            AssessmentCoinRewards::award($submission);

            return $submission;
        });

        if ($submission->wasRecentlyCreated) {
            NotificationSender::notifyAssessmentCompleted($submission->load(['assessment.teacher', 'student']));
            app(MLPredictionService::class)->predictForSubmission($submission);
        }

        $submission->loadMissing('mlPrediction');

        return response()->json([
            'attempt_number' => $submission->attempt_number,
            'correct_count' => $submission->correct_count,
            'question_count' => $submission->question_count,
            'points' => $submission->points,
            'possible_points' => $submission->possible_points,
            'coins_earned' => (int) ($submission->coinReward?->amount ?? 0),
            'coins_pending' => AssessmentCoinRewards::percentage($submission) === null,
            'phil_iri' => PhilIri::forSubmission($submission),
            'accuracy' => AssessmentScores::percentage($submission),
            'ml_prediction' => $submission->mlPrediction ? [
                'prediction' => $submission->mlPrediction->prediction,
                'confidence' => $submission->mlPrediction->confidence_score,
                'evaluation' => $submission->mlPrediction->model_evaluation,
                'recommendation' => $submission->mlPrediction->recommendation,
            ] : null,
        ]);
    }
}
