<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentProgress;
use App\Models\AssessmentRetakeRequest;
use App\Models\AssessmentSubmission;
use App\Models\User;
use App\Models\WorksheetAttempt;
use App\Support\AssessmentParticipant;
use App\Support\NotificationSender;
use App\Support\NumeracyWorksheets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WorksheetController extends Controller
{
    public function mission(Request $request)
    {
        abort_unless($request->user()->isStudent(), 403);
        return view('worksheets.mission', ['mission' => \App\Support\WorksheetMission::forStudent($request->user())]);
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->isTeacher(), 403);
        return view('worksheets.index', [
            'worksheets' => NumeracyWorksheets::all(),
            'assignments' => $request->user()->createdAssessments()->whereNotNull('worksheet_number')->latest()->get(),
            'reviews' => WorksheetAttempt::with(['assessment', 'student'])
                ->whereHas('assessment', fn ($query) => $query->where('created_by', $request->user()->id))
                ->whereNull('reviewed_at')->oldest()->get(),
        ]);
    }

    public function create(Request $request, int $number)
    {
        abort_unless($request->user()->isTeacher(), 403);
        return view('worksheets.create', ['worksheet' => NumeracyWorksheets::find($number)]);
    }

    public function store(Request $request, int $number)
    {
        abort_unless($request->user()->isTeacher(), 403);
        $worksheet = NumeracyWorksheets::find($number);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'target_section' => ['required', Rule::in(['all', 'section_a', 'section_b', 'section_c'])],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'worksheet_total' => ['required', 'integer', 'min:1', 'max:1000'],
            'retry_limit' => ['required', Rule::in(array_map('strval', array_keys(Assessment::retryLimitOptions())))],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ]);
        $assessment = $request->user()->createdAssessments()->create(array_merge($data, [
            'subject' => 'numeracy', 'quiz_type' => 'worksheet', 'delivery_method' => 'manual',
            'assessment_type' => 'worksheet', 'worksheet_number' => $worksheet['number'],
            'focus_areas' => ['Problem Solving'], 'manual_questions' => [],
            'retry_limit' => Assessment::normalizeRetryLimit($data['retry_limit']),
        ]));
        if ($assessment->status === 'published') NotificationSender::notifyAssessmentPublished($assessment);
        return redirect()->route('assessments.show', $assessment)->with('status', 'Worksheet assessment created.');
    }

    public function image(Request $request, int $number, int $part)
    {
        $worksheet = NumeracyWorksheets::find($number);
        $user = $request->user();
        if (! $user->isTeacher()) {
            abort_unless($user->isStudent(), 403);
            $section = $user->section ? strtolower(str_replace(' ', '_', $user->section)) : null;
            $assigned = Assessment::where('worksheet_number', $number)->where('status', 'published')
                ->where(fn ($query) => $query->whereIn('target_section', ['all', $section])->orWhereNull('target_section'))->exists();
            $submitted = WorksheetAttempt::where('user_id', $user->id)
                ->whereHas('assessment', fn ($query) => $query->where('worksheet_number', $number))->exists();
            abort_unless($assigned || $submitted, 404);
        }
        $page = collect($worksheet['pages'])->firstWhere('part', $part) ?? abort(404);
        $filename = $page['image'];
        if ($request->has('figure')) {
            $allowed = collect($page['reading']['sections'])->flatMap(fn ($section) => $section['items'] ?? [])
                ->filter(fn ($item) => is_array($item) && isset($item['figure']))->pluck('figure');
            abort_unless(is_string($request->query('figure')) && $allowed->containsStrict($request->query('figure')), 404);
            $filename = $request->query('figure');
        }
        return response()->file(resource_path('worksheets/aral-g6/'.$filename), ['Cache-Control' => 'private, max-age=3600']);
    }

    public function attribution()
    {
        return response()->file(resource_path('worksheets/aral-g6/attribution.pdf'), ['Cache-Control' => 'private, max-age=3600']);
    }

    private function authorizeStudent(Request $request, Assessment $assessment, ?User $student): User
    {
        $student = AssessmentParticipant::resolve($request, $assessment, $student);
        abort_unless($assessment->worksheet_number, 404);

        return $student;
    }

    private function validatedState(Request $request, Assessment $assessment): array
    {
        abort_if(strlen($request->getContent()) > 1500000, 413, 'This drawing is too large. Remove some strokes before saving.');
        $templates = NumeracyWorksheets::find($assessment->worksheet_number)['pages'];
        $count = count($templates);
        $data = $request->validate([
            'attempt_key' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1', 'max:9007199254740991'],
            'page' => ['required', 'integer', 'min:0', 'max:'.($count - 1)],
            'step' => ['sometimes', 'required', Rule::in(['read', 'answer'])],
            'pages' => ['required', 'array', 'size:'.$count],
            'pages.*' => ['required', 'array:text,strokes,answers,shading,responses'],
            'pages.*.responses' => ['sometimes', 'array', 'max:100'],
            'pages.*.text' => ['present', 'nullable', 'string', 'max:10000'],
            'pages.*.answers' => ['sometimes', 'array', 'max:100'],
            'pages.*.answers.*' => ['required', 'array:label,answer'],
            'pages.*.answers.*.label' => ['present', 'nullable', 'string', 'max:40'],
            'pages.*.answers.*.answer' => ['present', 'nullable', 'string', 'max:2000'],
            'pages.*.shading' => ['sometimes', 'array', 'max:100'],
            'pages.*.shading.*' => ['array', 'max:100'],
            'pages.*.shading.*.*' => ['integer', 'between:0,99'],
            'pages.*.strokes' => ['present', 'array', 'max:300'],
            'pages.*.strokes.*' => ['array:color,width,points'],
            'pages.*.strokes.*.color' => ['required', Rule::in(['#174d97', '#252c35', '#d73742'])],
            'pages.*.strokes.*.width' => ['required', 'numeric', 'between:1,12'],
            'pages.*.strokes.*.points' => ['required', 'array', 'min:1', 'max:1000'],
            'pages.*.strokes.*.points.*' => ['array', 'size:2'],
            'pages.*.strokes.*.points.*.*' => ['required', 'numeric', 'between:0,1'],
        ]);
        abort_unless(array_keys($data['pages']) === range(0, $count - 1), 422, 'Invalid worksheet pages.');
        foreach ($data['pages'] as $pageIndex => &$page) {
            $page['text'] = $page['text'] ?? '';
            if (isset($page['responses'])) $page['responses'] = \App\Support\WorksheetResponses::validate($page['responses'], $templates[$pageIndex]['responses']);
            if (isset($page['answers'])) {
                abort_unless(array_is_list($page['answers']), 422, 'Invalid worksheet answers.');
                $page['answers'] = array_map(fn ($entry) => ['label' => $entry['label'] ?? '', 'answer' => $entry['answer'] ?? ''], $page['answers']);
            }
            $grids = NumeracyWorksheets::grids($templates[$pageIndex]);
            foreach ($page['shading'] ?? [] as $key => $cells) {
                abort_unless(isset($grids[$key]) && array_is_list($cells) && count($cells) === count(array_unique($cells)), 422, 'Invalid fraction model.');
                foreach ($cells as $cell) abort_unless($cell < $grids[$key], 422, 'Invalid fraction model cell.');
            }
            abort_unless(array_is_list($page['strokes']), 422, 'Invalid drawing strokes.');
            foreach ($page['strokes'] as $stroke) {
                abort_unless(array_is_list($stroke['points']), 422, 'Invalid drawing points.');
                foreach ($stroke['points'] as $point) abort_unless(array_keys($point) === [0, 1], 422, 'Invalid drawing point.');
            }
        }
        return $data;
    }

    public function save(Request $request, Assessment $assessment, ?User $student = null)
    {
        $student = $this->authorizeStudent($request, $assessment, $student);
        $data = $this->validatedState($request, $assessment);
        return DB::transaction(function () use ($request, $student, $assessment, $data) {
            User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $progress = $this->progress($student, $assessment, $data['attempt_key']);
            abort_if($progress->submission_id || WorksheetAttempt::where('progress_id', $progress->id)->exists(), 409, 'Worksheet already submitted.');
            if ($data['revision'] > $progress->revision) {
                $progress->update(['revision' => $data['revision'], 'state' => ['pages' => $data['pages'], 'page' => $data['page'], 'step' => $data['step'] ?? 'read', 'phase' => 'questions']]);
                $progress->recordAssistance($request->user());
            }
            return response()->json(['revision' => $progress->revision]);
        });
    }

    private function progress(User $student, Assessment $assessment, string $key): AssessmentProgress
    {
        return AssessmentProgress::where('assessment_id', $assessment->id)->where('user_id', $student->id)
            ->where('attempt_key', $key)->firstOrFail();
    }

    public function submit(Request $request, Assessment $assessment, ?User $student = null)
    {
        $student = $this->authorizeStudent($request, $assessment, $student);
        $data = $this->validatedState($request, $assessment);
        $attempt = DB::transaction(function () use ($request, $student, $assessment, $data) {
            $student = User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $progress = $this->progress($student, $assessment, $data['attempt_key']);
            $existing = WorksheetAttempt::where('progress_id', $progress->id)->first();
            if ($existing) return $existing;
            abort_unless($data['revision'] >= $progress->revision, 409, 'Newer answers were saved in another tab. Reload before submitting.');
            $count = $assessment->submissions()->where('user_id', $student->id)->count();
            abort_unless(! $progress->submission_id && $progress->attempt_number === $count + 1, 409);
            $templates = NumeracyWorksheets::find($assessment->worksheet_number)['pages'];
            foreach ($data['pages'] as $pageIndex => $page) {
                if ($templates[$pageIndex]['reading']['reading_only'] ?? false) continue;
                $hasAnswers = collect($page['answers'] ?? [])->contains(fn ($entry) => trim($entry['answer']) !== '');
                $hasShading = collect($page['shading'] ?? [])->contains(fn ($cells) => count($cells) > 0);
                $hasResponses = \App\Support\WorksheetResponses::hasAnswer($page['responses'] ?? []);
                abort_unless($hasResponses || $hasAnswers || $hasShading || trim($page['text']) !== '' || count($page['strokes']) > 0, 422, 'Add an answer or drawing to every exercise part before submitting.');
            }
            if ($assessment->remainingIncludedAttempts($count) === 0) {
                $token = AssessmentRetakeRequest::where('assessment_id', $assessment->id)->where('user_id', $student->id)
                    ->where('status', 'approved')->where('remaining_tries', '>', 0)->oldest('decided_at')->lockForUpdate()->first();
                abort_unless($token, 403, 'A teacher-approved retake token is required.');
                $token->decrement('remaining_tries');
            }
            $attempt = WorksheetAttempt::create(['assessment_id' => $assessment->id, 'user_id' => $student->id,
                'progress_id' => $progress->id, 'pages' => $data['pages'], 'total' => $assessment->worksheet_total]);
            $progress->update(['revision' => $data['revision'], 'state' => ['pages' => $data['pages'], 'page' => $data['page'], 'step' => 'answer', 'phase' => 'finished']]);
            $progress->recordAssistance($request->user());
            NotificationSender::sendToUsers([$assessment->teacher], 'worksheet_submitted', 'Worksheet ready for review',
                $student->name.' submitted '.$assessment->title, route('worksheets.review', $attempt));
            return $attempt;
        });
        return response()->json(['url' => route('worksheets.review', $attempt)]);
    }

    public function review(Request $request, WorksheetAttempt $attempt)
    {
        $attempt->load(['assessment', 'student', 'progress.submission', 'progress.administrator']);
        $teacher = $request->user()->isTeacher() && $attempt->assessment->created_by === $request->user()->id;
        abort_unless($teacher || ($request->user()->isStudent() && $attempt->user_id === $request->user()->id), 404);
        return view('worksheets.review', ['attempt' => $attempt, 'assessment' => $attempt->assessment,
            'worksheet' => NumeracyWorksheets::find($attempt->assessment->worksheet_number), 'teacher' => $teacher]);
    }

    public function multiplicationTable(Request $request, Assessment $assessment, ?User $student = null)
    {
        $student = $this->authorizeStudent($request, $assessment, $student);
        $request->validate(['attempt_key' => ['required', 'uuid'], 'password' => ['sometimes', 'required', 'string', 'max:1024']]);
        return DB::transaction(function () use ($request, $student, $assessment) {
            User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $progress = $this->progress($student, $assessment, $request->input('attempt_key'));
            abort_if($progress->submission_id || WorksheetAttempt::where('progress_id', $progress->id)->exists(), 409, 'This attempt is already submitted.');
            $teacher = $assessment->teacher;
            abort_unless($teacher && $teacher->isTeacher() && $teacher->isApproved(), 403, 'Teacher approval is unavailable.');
            if (! $progress->multiplication_table_unlocked_at) {
                $request->validate(['password' => ['required', 'string', 'max:1024']]);
                $key = 'worksheet-table:'.$teacher->id.':'.$request->ip();
                abort_if(RateLimiter::tooManyAttempts($key, 5), 429, 'Too many password attempts. Try again in a minute.');
                RateLimiter::hit($key, 60);
                if (! Hash::check($request->input('password'), $teacher->password)) {
                    throw ValidationException::withMessages(['password' => "The assessment teacher's password was not accepted."]);
                }
                $progress->forceFill(['multiplication_table_unlocked_at' => now()])->save();
            }
            return response()->json(['table' => array_map(fn ($row) => array_map(fn ($column) => $row * $column, range(1, 12)), range(1, 12))])
                ->header('Cache-Control', 'private, no-store');
        });
    }

    public function grade(Request $request, WorksheetAttempt $attempt)
    {
        abort_unless($request->user()->isTeacher(), 403);
        abort_unless($attempt->assessment->created_by === $request->user()->id, 404);
        $data = $request->validate(['score' => ['required', 'integer', 'min:0', 'max:'.$attempt->total],
            'feedback' => ['required', 'string', 'max:5000']]);
        DB::transaction(function () use ($attempt, $data) {
            User::whereKey($attempt->user_id)->lockForUpdate()->firstOrFail();
            $attempt = WorksheetAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            if ($attempt->reviewed_at) return;
            $submission = AssessmentSubmission::create([
                'assessment_id' => $attempt->assessment_id, 'user_id' => $attempt->user_id,
                'attempt_number' => $attempt->progress->attempt_number, 'answers' => [],
                'correct_count' => $data['score'], 'question_count' => $attempt->total,
                'points' => $data['score'] * 250, 'possible_points' => $attempt->total * 250, 'submitted_at' => $attempt->created_at,
            ]);
            $attempt->progress->update(['submission_id' => $submission->id]);
            $attempt->update(['score' => $data['score'], 'feedback' => $data['feedback'], 'reviewed_at' => now()]);
            NotificationSender::sendToUsers([$attempt->student], 'worksheet_graded', 'Your worksheet has been checked',
                $attempt->assessment->title, route('worksheets.review', $attempt));
        });
        return back()->with('status', 'Worksheet score saved.');
    }
}
