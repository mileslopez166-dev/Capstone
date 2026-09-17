<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AssessmentSubmission;
use App\Models\PracticeMission;
use App\Support\NotificationSender;
use App\Support\PracticeSuggestions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PracticeController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isTeacher(), 403);
        $studentId = $request->integer('student');

        return view('practice.teacher-index', [
            'missions' => PracticeMission::where('teacher_id', $request->user()->id)
                ->when($studentId, fn ($query) => $query->where('student_id', $studentId))
                ->with('student')->withCount('attempts')->latest()->paginate(12, ['*'], 'missions')->withQueryString(),
            'submissions' => AssessmentSubmission::whereHas('assessment', fn ($query) => $query->where('created_by', $request->user()->id))
                ->whereHas('student', fn ($query) => $query->where('role', 'student'))
                ->when($studentId, fn ($query) => $query->where('user_id', $studentId))
                ->with(['assessment', 'student'])->latest('submitted_at')->paginate(12, ['*'], 'results')->withQueryString(),
            'studentId' => $studentId,
        ]);
    }

    public function create(Request $request, AssessmentSubmission $submission)
    {
        $this->authorizeSource($request, $submission);
        $existing = PracticeMission::where('submission_id', $submission->id)->first();
        if ($existing) {
            return redirect()->route('teacher.practice.show', $existing);
        }

        return view('practice.create', [
            'submission' => $submission,
            'suggestions' => PracticeSuggestions::forSubmission($submission),
        ]);
    }

    public function store(Request $request, AssessmentSubmission $submission)
    {
        $this->authorizeSource($request, $submission);
        $suggestions = PracticeSuggestions::forSubmission($submission);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*' => ['required', 'string', 'distinct', Rule::in(array_keys($suggestions))],
        ], ['items.max' => 'Choose up to 10 items for one practice mission.']);

        $mission = DB::transaction(function () use ($request, $submission, $data, $suggestions) {
            AssessmentSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $existing = PracticeMission::where('submission_id', $submission->id)->first();
            if ($existing) {
                return $existing;
            }
            $selected = collect($suggestions)->only($data['items']);
            $mission = PracticeMission::create([
                'teacher_id' => $request->user()->id, 'student_id' => $submission->user_id,
                'submission_id' => $submission->id, 'title' => $data['title'],
                'source_title' => $submission->assessment->title,
                'story' => $submission->assessment->story_description ?: $submission->assessment->instructions,
                'instructions' => $data['instructions'] ?? null,
                'questions' => $selected->filter(fn ($item) => isset($item['question']))->values()->all(),
                'words' => $selected->filter(fn ($item) => isset($item['word']))->values()->all(),
                'reward_coins' => 25,
            ]);
            NotificationSender::sendToUsers([$submission->student], 'practice_assigned', 'New practice mission',
                $mission->title, route('student.practice.show', $mission));

            return $mission;
        });

        return redirect()->route('teacher.practice.show', $mission)->with('practice_status', 'Practice mission assigned.');
    }

    public function show(Request $request, PracticeMission $mission)
    {
        $this->authorizeMission($request, $mission);

        return view('practice.teacher-show', [
            'mission' => $mission->load('student'),
            'attempts' => $mission->attempts()->latest('id')->paginate(10),
            'firstAttempt' => $mission->attempts()->oldest('id')->first(),
        ]);
    }

    public function review(Request $request, PracticeMission $mission)
    {
        $this->authorizeMission($request, $mission);
        abort_unless($mission->status === 'completed', 409);
        $keys = array_keys($mission->words);
        $rules = ['feedback' => ['nullable', 'string', 'max:2000'],
            'word_reviews' => ['sometimes', 'array'.($keys ? ':'.implode(',', $keys) : ''), 'max:'.count($keys)]];
        foreach ($keys as $index) {
            $rules['word_reviews.'.$index] = ['required', Rule::in(['clear', 'getting_closer', 'mispronounced'])];
        }
        $data = $request->validate($rules);
        DB::transaction(function () use ($mission, $data) {
            $mission = PracticeMission::whereKey($mission->id)->lockForUpdate()->firstOrFail();
            $firstReview = !$mission->reviewed_at;
            $mission->update(['feedback' => $data['feedback'] ?? null,
                'word_reviews' => $data['word_reviews'] ?? [], 'reviewed_at' => now()]);
            if ($firstReview) {
                NotificationSender::sendToUsers([$mission->student], 'practice_reviewed', 'Your practice was reviewed',
                    $mission->title, route('student.practice.show', $mission));
            }
        });

        return back()->with('practice_status', 'Practice feedback saved.');
    }

    public function cancel(Request $request, PracticeMission $mission)
    {
        $this->authorizeMission($request, $mission);
        PracticeMission::whereKey($mission->id)->where('status', 'assigned')->update(['status' => 'cancelled']);

        return back()->with('practice_status', 'Mission updated. Completed missions keep their earned reward.');
    }

    private function authorizeSource(Request $request, AssessmentSubmission $submission): void
    {
        abort_unless($request->user()->isTeacher(), 403);
        abort_unless($submission->assessment?->created_by === $request->user()->id
            && $submission->student?->isStudent(), 404);
    }

    private function authorizeMission(Request $request, PracticeMission $mission): void
    {
        abort_unless($request->user()->isTeacher(), 403);
        abort_unless($mission->teacher_id === $request->user()->id, 404);
    }
}
