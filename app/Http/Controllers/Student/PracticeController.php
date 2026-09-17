<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\PracticeMission;
use App\Models\User;
use App\Support\NotificationSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PracticeController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isStudent(), 403);

        return view('practice.student-index', [
            'missions' => PracticeMission::where('student_id', $request->user()->id)->with('teacher')
                ->orderByRaw("CASE WHEN status = 'assigned' THEN 0 ELSE 1 END")->latest()->paginate(12),
            'coinBalance' => $request->user()->practiceCoinBalance(),
        ]);
    }

    public function show(Request $request, PracticeMission $mission)
    {
        $this->authorizeMission($request, $mission);

        return view('practice.student-show', [
            'mission' => $mission->load('teacher'),
            'latestAttempt' => $mission->attempts()->latest('id')->first(),
            'attemptKey' => (string) Str::uuid(),
        ]);
    }

    public function submit(Request $request, PracticeMission $mission)
    {
        $this->authorizeMission($request, $mission);
        $keys = array_keys($mission->questions);
        $wordKeys = array_keys($mission->words);
        $checking = $request->input('action') === 'check';
        $rules = [
            'action' => ['required', Rule::in(['save', 'check'])],
            'attempt_key' => ['required', 'uuid'],
            'answers' => ['sometimes', 'array'.($keys ? ':'.implode(',', $keys) : ''), 'max:'.count($keys)],
            'practiced_words' => [$checking && $wordKeys ? 'required' : 'sometimes', 'array', 'max:'.count($wordKeys)],
            'practiced_words.*' => ['integer', 'distinct', Rule::in($wordKeys)],
        ];
        foreach ($keys as $index) {
            $rules['answers.'.$index] = [$checking ? 'required' : 'sometimes', Rule::in(array_keys($mission->questions[$index]['answers']))];
        }
        if ($checking && $wordKeys) {
            $rules['practiced_words'][] = 'size:'.count($wordKeys);
        }
        $data = $request->validate($rules, [
            'practiced_words.required' => 'Practice each word before checking your mission.',
            'practiced_words.size' => 'Practice each word before checking your mission.',
            'answers.*.required' => 'Choose an answer for every question.',
        ]);

        $message = DB::transaction(function () use ($request, $mission, $data, $checking) {
            // Wallet operations always lock the student first, including wardrobe purchases.
            $student = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $mission = PracticeMission::whereKey($mission->id)->lockForUpdate()->firstOrFail();
            if ($mission->status !== 'assigned') {
                return $mission->status === 'completed' ? 'This mission is already complete.' : 'Your teacher cancelled this mission.';
            }
            if ($mission->attempts()->where('attempt_key', $data['attempt_key'])->exists()) {
                return 'Your practice was already checked.';
            }
            // Reject a key already used on another mission without exposing its owner.
            abort_if(\App\Models\PracticeAttempt::where('attempt_key', $data['attempt_key'])->exists(), 409);
            $answers = $data['answers'] ?? [];
            $practicedWords = array_map('intval', $data['practiced_words'] ?? []);
            $mission->update(['progress' => ['answers' => $answers, 'practiced_words' => $practicedWords]]);
            if (!$checking) {
                return 'Progress saved. You can continue later.';
            }
            $correct = collect($mission->questions)->filter(fn ($question, $index) => ($answers[$index] ?? null) === $question['correct_answer'])->count();
            $mission->attempts()->create([
                'attempt_key' => $data['attempt_key'], 'answers' => $answers, 'practiced_words' => $practicedWords,
                'correct_count' => $correct, 'question_count' => count($mission->questions),
            ]);
            if ($correct !== count($mission->questions)) {
                return 'Practice checked. Review the feedback and try the questions again.';
            }
            $mission->update(['status' => 'completed', 'completed_at' => now()]);
            $student->practiceCoinTransactions()->create([
                'practice_mission_id' => $mission->id, 'amount' => $mission->reward_coins,
                'description' => 'Completed practice: '.$mission->title,
            ]);
            NotificationSender::sendToUsers([$mission->teacher], 'practice_completed', 'Practice ready for review',
                $student->name.' completed '.$mission->title, route('teacher.practice.show', $mission));

            return 'Mission complete! You earned '.$mission->reward_coins.' practice coins.';
        });

        return redirect()->route('student.practice.show', $mission)->with('practice_status', $message);
    }

    private function authorizeMission(Request $request, PracticeMission $mission): void
    {
        abort_unless($request->user()->isStudent(), 403);
        abort_unless($mission->student_id === $request->user()->id, 404);
    }
}
