<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\StudentTutor;
use App\Support\TutorUnavailable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AiAssistantController extends Controller
{
    public function index(Request $request, StudentTutor $assistant): View
    {
        $teacher = $this->teacher($request);

        return view('teacher.ai-assistant', [
            'teacher' => $teacher,
            'available' => $assistant->available(),
            'teacherAiConfig' => [
                'available' => $assistant->available(),
                'sendUrl' => route('teacher.ai-assistant.send'),
                'turns' => [],
                'prompts' => [
                    'Create a Grade 6 silent reading story with 5 comprehension questions.',
                    'Explain how to interpret an Instructional Phil-IRI result.',
                    'Suggest a short intervention for weak vocabulary and comprehension.',
                ],
            ],
        ]);
    }

    public function send(Request $request, StudentTutor $assistant): JsonResponse
    {
        $teacher = $this->teacher($request);
        $data = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'request_id' => ['required', 'uuid'],
        ]);

        return $this->locked($teacher, function () use ($teacher, $data, $assistant) {
            if (! $assistant->available()) {
                return response()->json(['message' => 'Teachers AI Assistant is not connected yet. Add the OpenAI API key first.'], 503);
            }

            foreach (['minute' => [8, 60], 'day' => [40, 86400]] as $period => [$limit, $seconds]) {
                if (RateLimiter::tooManyAttempts('teacher-ai:'.$teacher->id.':'.$period, $limit)) {
                    return response()->json(['message' => $period === 'day'
                        ? 'You have reached today\'s assistant limit. Please try again tomorrow.'
                        : 'Give Teachers AI Assistant a moment. Please try again in a minute.'], 429);
                }
            }
            RateLimiter::hit('teacher-ai:'.$teacher->id.':minute', 60);
            RateLimiter::hit('teacher-ai:'.$teacher->id.':day', 86400);

            try {
                $reply = $assistant->replyForTeacherAssistant($data['question']);
            } catch (TutorUnavailable $exception) {
                return response()->json(['message' => $exception->status === 503
                    ? 'Teachers AI Assistant could not reply right now. Please try again later.'
                    : $exception->getMessage()], $exception->status);
            }

            return response()->json([
                'turn' => [
                    'id' => $data['request_id'],
                    'question' => $data['question'],
                    'answer' => $reply->answer,
                ],
            ]);
        });
    }

    private function teacher(Request $request): User
    {
        $teacher = $request->user();
        abort_unless($teacher?->isTeacher() && $teacher->isApproved(), 403);

        return $teacher;
    }

    private function locked(User $teacher, callable $action)
    {
        $lock = Cache::lock('teacher-ai:'.$teacher->id, 90);
        if (! $lock->get()) {
            return response()->json(['message' => 'Please wait for your current assistant request to finish.'], 429);
        }

        try {
            return $action();
        } finally {
            $lock->release();
        }
    }
}
