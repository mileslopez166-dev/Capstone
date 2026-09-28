<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAnswerFeedback;
use App\Models\AssessmentSubmission;
use App\Models\TutorChat;
use App\Models\TutorTurn;
use App\Models\User;
use App\Support\AssessmentParticipant;
use App\Support\NotificationSender;
use App\Support\StudentTutor;
use App\Support\TutorReply;
use App\Support\TutorUnavailable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class TutorController extends Controller
{
    public function index(Request $request, StudentTutor $tutor, ?int $chat = null)
    {
        $student = $this->student($request);
        $current = $chat ? $this->ownedChat($student, $chat) : null;
        $data = $request->validate(['submission' => ['nullable', 'integer'], 'subject' => ['nullable', Rule::in(['literacy', 'numeracy'])]]);
        $submission = $this->submission($student, $current ? $current->assessment_submission_id : ($data['submission'] ?? null));
        $subject = $current?->subject ?? $submission?->assessment?->subject ?? ($data['subject'] ?? 'literacy');
        $chats = TutorChat::where('user_id', $student->id)->with('submission.assessment')->latest('updated_at')->paginate(12);
        $turns = $current?->turns()->orderBy('id')->get(['id', 'question', 'answer']) ?? collect();

        return view('student.tutor', compact('student', 'current', 'submission', 'subject', 'chats', 'turns') + ['available' => $tutor->available()]);
    }

    public function send(Request $request, StudentTutor $tutor)
    {
        $student = $this->student($request);
        $data = $request->validate([
            'chat_id' => ['nullable', 'integer'],
            'submission_id' => ['nullable', 'integer'],
            'subject' => ['required', Rule::in(['literacy', 'numeracy'])],
            'question' => ['required', 'string', 'max:1500'],
            'request_id' => ['required', 'uuid'],
        ]);

        return $this->locked($student, function () use ($student, $data, $tutor) {
            $chat = isset($data['chat_id']) ? $this->ownedChat($student, $data['chat_id']) : null;
            $submission = $this->submission($student, $chat ? $chat->assessment_submission_id : ($data['submission_id'] ?? null));
            $subject = $chat?->subject ?? $submission?->assessment?->subject ?? $data['subject'];
            $existing = TutorTurn::where('request_id', $data['request_id'])->first();
            if ($existing) {
                abort_unless($existing->chat->user_id === $student->id, 404);
                abort_if($existing->question !== $data['question'] || ($chat && $chat->id !== $existing->tutor_chat_id), 409, 'Start a new request for this question.');
                abort_if($existing->chat->subject !== $subject || (int) $existing->chat->assessment_submission_id !== (int) $submission?->id, 409, 'Start a new request for this learning material.');

                return $this->replyResponse($existing->chat, $existing);
            }
            if ($chat && $chat->turns()->count() >= 40) {
                return response()->json(['message' => 'This conversation is full. Start a new chat to keep learning.'], 422);
            }
            if (! $tutor->available()) {
                return response()->json(['message' => 'Ask Tutor is not connected yet. Please ask your teacher for help for now.'], 503);
            }
            if ($reason = $tutor->localTeacherAttentionReason($data['question'], $subject, $chat, $submission)) {
                [$chat, $turn] = $this->storeTutorTurn($chat, $student, $subject, $submission, $data, new TutorReply($tutor->outOfScopeAnswer(), $reason));
                $this->notifyTeacherAttention($student, $chat, $submission, $subject, $reason);

                return $this->replyResponse($chat, $turn);
            }
            foreach (['minute' => [config('tutor.per_minute'), 60], 'day' => [config('tutor.per_day'), 86400]] as $period => [$limit, $seconds]) {
                if (RateLimiter::tooManyAttempts('tutor:'.$student->id.':'.$period, $limit)) {
                    return response()->json(['message' => $period === 'day'
                        ? 'You have reached today\'s tutor limit. Ask your teacher for help, or try again tomorrow.'
                        : 'Give the tutor a moment. Please try again in a minute.'], 429);
                }
            }
            RateLimiter::hit('tutor:'.$student->id.':minute', 60);
            RateLimiter::hit('tutor:'.$student->id.':day', 86400);
            try {
                $reply = $tutor->replyWithSignals($data['question'], $subject, $chat, $submission);
            } catch (TutorUnavailable $exception) {
                $this->notifyTeacherAttention($student, $chat, $submission, $subject, $exception->teacherAttentionReason);

                return response()->json(['message' => $exception->getMessage()], $exception->status);
            }

            [$chat, $turn] = $this->storeTutorTurn($chat, $student, $subject, $submission, $data, $reply);

            $this->notifyTeacherAttention($student, $chat, $submission, $subject, $reply->teacherAttentionReason);

            return $this->replyResponse($chat, $turn);
        });
    }

    public function answerReview(Request $request, int $submission, int $question, StudentTutor $tutor)
    {
        $student = $this->student($request);
        $submission = $this->submission($student, $submission);
        abort_unless($submission?->assessment && $submission->scorePercentage() !== null && ! $submission->worksheetAttempt, 404);
        $item = array_values($submission->questionsForReview())[$question] ?? null;
        abort_unless(is_array($item), 404);
        $correct = $item['correct_answer'] ?? null;
        $selected = ($submission->answers ?? [])[$question] ?? null;
        abort_unless(is_string($correct) && isset($item['answers'][$correct]), 422, 'Ask your teacher to check the answer key for this question.');
        abort_if($selected === $correct, 422, 'This answer is already correct. You can ask the tutor about the topic.');

        $material = $tutor->reviewMaterial($submission, $item);
        $material['selected_answer'] = is_string($selected) && isset($item['answers'][$selected]) ? $selected : null;
        $hash = hash('sha256', json_encode($material, JSON_INVALID_UTF8_SUBSTITUTE));

        return $this->locked($student, function () use ($student, $submission, $question, $tutor, $material, $hash) {
            $identity = ['assessment_submission_id' => $submission->id, 'question_index' => $question];
            $saved = AssessmentAnswerFeedback::where($identity)->first();
            if ($saved && hash_equals($saved->source_hash, $hash)) {
                return response()->json(['answer' => $saved->answer, 'saved' => true])->header('Cache-Control', 'private, no-store');
            }
            if (! $tutor->available()) {
                return response()->json(['message' => 'The AI helper is unavailable. Your result is saved; please ask your teacher for help.'], 503);
            }
            foreach (['minute' => [config('tutor.per_minute'), 60], 'day' => [config('tutor.per_day'), 86400]] as $period => [$limit, $seconds]) {
                if (RateLimiter::tooManyAttempts('tutor:'.$student->id.':'.$period, $limit)) {
                    return response()->json(['message' => $period === 'day'
                        ? 'You have reached today\'s tutor limit. Saved explanations are still available. Ask your teacher for more help.'
                        : 'Give the tutor a moment. Please try again in a minute.'], 429);
                }
            }
            RateLimiter::hit('tutor:'.$student->id.':minute', 60);
            RateLimiter::hit('tutor:'.$student->id.':day', 86400);
            try {
                $reply = $tutor->replyForAnswerReview($material);
            } catch (TutorUnavailable $exception) {
                $this->notifyTeacherAttention($student, null, $submission, $material['subject'], $exception->teacherAttentionReason);

                return response()->json(['message' => $exception->getMessage()], $exception->status);
            }
            if ($reply->teacherAttentionReason) {
                $this->notifyTeacherAttention($student, null, $submission, $material['subject'], $reply->teacherAttentionReason);

                return response()->json(['message' => 'Please ask your teacher to help review this question.'], 422);
            }
            AssessmentAnswerFeedback::updateOrCreate($identity, ['source_hash' => $hash, 'answer' => $reply->answer]);

            return response()->json(['answer' => $reply->answer, 'saved' => true])->header('Cache-Control', 'private, no-store');
        });
    }

    public function assessmentHelp(Request $request, Assessment $assessment, StudentTutor $tutor)
    {
        $student = $this->student($request);
        abort_unless($assessment->status === 'published' && AssessmentParticipant::matchesSection($assessment, $student), 404);
        $assessment = clone $assessment;
        $active = \App\Models\AssessmentProgress::where('assessment_id', $assessment->id)
            ->where('user_id', $student->id)->whereNull('submission_id')->latest('attempt_number')->first();
        $assessment->manual_questions = $active?->question_snapshot
            ?? ($assessment->question_selection === 'automatic' ? [] : $assessment->manual_questions);

        $data = $request->validate([
            'chat_id' => ['nullable', 'integer'],
            'question' => ['required', 'string', 'max:1500'],
            'request_id' => ['required', 'uuid'],
        ]);

        return $this->locked($student, function () use ($student, $assessment, $data, $tutor) {
            $chat = isset($data['chat_id']) ? $this->ownedChat($student, $data['chat_id']) : null;
            abort_if($chat && ($chat->assessment_submission_id || $chat->subject !== $assessment->subject), 409, 'Start a new request for this assessment.');
            $existing = TutorTurn::where('request_id', $data['request_id'])->first();
            if ($existing) {
                abort_unless($existing->chat->user_id === $student->id, 404);
                abort_if($existing->question !== $data['question'] || ($chat && $chat->id !== $existing->tutor_chat_id), 409, 'Start a new request for this question.');

                return $this->replyResponse($existing->chat, $existing);
            }
            if ($chat && $chat->turns()->count() >= 20) {
                return response()->json(['message' => 'This assessment helper chat is full. Refresh the page to start a fresh helper.'], 422);
            }
            if (! $tutor->available()) {
                return response()->json(['message' => 'Ask Tutor is not connected yet. Please ask your teacher for help for now.'], 503);
            }
            if ($reason = $tutor->localTeacherAttentionReason($data['question'], $assessment->subject, $chat, null, $assessment)) {
                [$chat, $turn] = $this->storeTutorTurn($chat, $student, $assessment->subject, null, $data, new TutorReply($tutor->outOfScopeAnswer(), $reason));
                $this->notifyTeacherAttention($student, $chat, null, $assessment->subject, $reason, $assessment);

                return $this->replyResponse($chat, $turn);
            }
            foreach (['minute' => [config('tutor.per_minute'), 60], 'day' => [config('tutor.per_day'), 86400]] as $period => [$limit, $seconds]) {
                if (RateLimiter::tooManyAttempts('tutor:'.$student->id.':assessment:'.$period, $limit)) {
                    return response()->json(['message' => $period === 'day'
                        ? 'You have reached today\'s tutor limit. Ask your teacher for help, or try again tomorrow.'
                        : 'Give the tutor a moment. Please try again in a minute.'], 429);
                }
            }
            RateLimiter::hit('tutor:'.$student->id.':assessment:minute', 60);
            RateLimiter::hit('tutor:'.$student->id.':assessment:day', 86400);

            try {
                $reply = $tutor->replyForAssessmentHelp($data['question'], $assessment, $chat);
            } catch (TutorUnavailable $exception) {
                $this->notifyTeacherAttention($student, $chat, null, $assessment->subject, $exception->teacherAttentionReason, $assessment);

                return response()->json(['message' => $exception->getMessage()], $exception->status);
            }

            [$chat, $turn] = $this->storeTutorTurn($chat, $student, $assessment->subject, null, $data, $reply);

            $this->notifyTeacherAttention($student, $chat, null, $assessment->subject, $reply->teacherAttentionReason, $assessment);

            return $this->replyResponse($chat, $turn);
        });
    }

    private function storeTutorTurn(?TutorChat $chat, User $student, string $subject, ?AssessmentSubmission $submission, array $data, TutorReply $reply): array
    {
        return DB::transaction(function () use ($chat, $student, $subject, $submission, $data, $reply) {
            $chat ??= TutorChat::create([
                'user_id' => $student->id, 'subject' => $subject,
                'assessment_submission_id' => $submission?->id,
            ]);
            $turn = $chat->turns()->create(['request_id' => $data['request_id'], 'question' => $data['question'], 'answer' => $reply->answer]);
            $chat->touch();

            return [$chat, $turn];
        });
    }

    public function destroy(Request $request, int $chat)
    {
        $student = $this->student($request);

        return $this->locked($student, function () use ($student, $chat) {
            $this->ownedChat($student, $chat)->delete();

            return response()->json(['url' => route('student.tutor.index')]);
        });
    }

    public function teacherHelp(Request $request, int $chat)
    {
        $student = $this->student($request);

        return $this->locked($student, function () use ($student, $chat) {
            $chat = $this->ownedChat($student, $chat);
            if ($chat->teacher_help_requested_at?->isAfter(now()->subDay())) {
                return response()->json(['message' => 'Your teacher has already been notified today.']);
            }
            $teachers = $this->teacherRecipients($student, $chat, $chat->submission);
            if ($teachers->isEmpty()) {
                return response()->json(['message' => 'No assigned teacher is available here. Please ask your teacher in class.'], 422);
            }
            DB::transaction(function () use ($teachers, $student, $chat) {
                NotificationSender::sendToUsers($teachers, 'tutor_help', 'Student needs learning support',
                    $student->name.' would like help with '.$chat->title().'. Please check in with them. No chat transcript is shared.',
                    route('students.show', $student));
                $chat->update(['teacher_help_requested_at' => now()]);
            });

            return response()->json(['message' => 'Your teacher has been notified. Your chat was not shared.']);
        });
    }

    private function notifyTeacherAttention(User $student, ?TutorChat $chat, ?AssessmentSubmission $submission, string $subject, ?string $reason, ?Assessment $assessment = null): void
    {
        if (! in_array($reason, ['off_topic', 'safety'], true)) {
            return;
        }

        $teachers = $this->teacherRecipients($student, $chat, $submission, $assessment);
        if ($teachers->isEmpty()) {
            return;
        }

        $cacheKey = 'tutor:teacher-attention:'.$student->id.':'.$reason;
        if (! Cache::add($cacheKey, true, now()->addDay())) {
            return;
        }

        $subjectLabel = ucfirst($chat?->subject ?? $submission?->assessment?->subject ?? $subject);
        $message = $reason === 'safety'
            ? $student->name.' may need a teacher check-in after an AI tutor safety redirect in '.$subjectLabel.'. No chat transcript is shared.'
            : $student->name.' asked the AI tutor for help outside literacy or numeracy support in '.$subjectLabel.'. Please check in when available. No chat transcript is shared.';

        NotificationSender::sendToUsers(
            $teachers,
            'tutor_attention',
            $reason === 'safety' ? 'Tutor safety check-in' : 'Tutor question needs follow-up',
            $message,
            route('students.show', $student)
        );
    }

    private function teacherRecipients(User $student, ?TutorChat $chat = null, ?AssessmentSubmission $submission = null, ?Assessment $assessment = null)
    {
        $teacher = $assessment?->teacher ?? ($chat?->submission ?? $submission)?->assessment?->teacher;
        if ($teacher?->isTeacher() && $teacher->isApproved()) {
            return collect([$teacher]);
        }

        return User::query()
            ->where('role', 'teacher')
            ->where('approval_status', 'approved')
            ->where('section', $student->section)
            ->when(blank($student->section), fn ($query) => $query->whereRaw('1 = 0'))
            ->get();
    }

    private function student(Request $request): User
    {
        $student = $request->user();
        abort_unless($student?->isStudent() && $student->isApproved(), 403);

        return $student;
    }

    private function ownedChat(User $student, int $id): TutorChat
    {
        return TutorChat::where('user_id', $student->id)->findOrFail($id);
    }

    private function submission(User $student, ?int $id): ?AssessmentSubmission
    {
        return $id ? AssessmentSubmission::where('user_id', $student->id)->whereNotNull('submitted_at')->with('assessment')->findOrFail($id) : null;
    }

    private function locked(User $student, callable $action)
    {
        $lock = Cache::lock('tutor:user:'.$student->id, 90);
        if (! $lock->get()) {
            return response()->json(['message' => 'Please wait for your current tutor request to finish.'], 429);
        }
        try {
            return $action();
        } finally {
            $lock->release();
        }
    }

    private function replyResponse(TutorChat $chat, TutorTurn $turn)
    {
        return response()->json([
            'chat_id' => $chat->id, 'title' => $chat->title(),
            'url' => route('student.tutor.index', $chat->id),
            'delete_url' => route('student.tutor.destroy', $chat->id),
            'help_url' => route('student.tutor.help', $chat->id),
            'turn' => $turn->only(['id', 'question', 'answer']),
        ]);
    }
}
