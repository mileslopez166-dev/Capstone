<?php

namespace App\Support;

use App\Models\AssessmentSubmission;
use App\Models\PracticeMission;
use Illuminate\Support\Facades\DB;

class PracticeMissionCreator
{
    public function createForSubmission(AssessmentSubmission $submission): ?PracticeMission
    {
        $mission = DB::transaction(function () use ($submission): ?PracticeMission {
            $locked = AssessmentSubmission::query()
                ->with(['assessment.teacher', 'student'])
                ->lockForUpdate()
                ->findOrFail($submission->id);

            if ($locked->assessment?->subject !== 'literacy'
                || ! $locked->assessment?->teacher
                || ! $locked->student?->isStudent()) {
                return null;
            }

            $recommendation = PhilIri::forSubmission($locked)['practice_recommendation'] ?? null;
            $suggestions = collect(PracticeSuggestions::forSubmission($locked))->take(10);

            if (! $recommendation || $suggestions->isEmpty()) {
                return null;
            }

            $existing = PracticeMission::query()->where('submission_id', $locked->id)->first();
            if ($existing) {
                return $existing;
            }

            $selected = $suggestions->all();

            return PracticeMission::create([
                'teacher_id' => $locked->assessment->created_by,
                'student_id' => $locked->user_id,
                'submission_id' => $locked->id,
                'title' => 'Practice: '.$locked->assessment->title,
                'source_title' => $locked->assessment->title,
                'story' => $locked->assessment->story_description ?: $locked->assessment->instructions,
                'instructions' => $recommendation['summary'] ?? 'Review these items and try again at your own pace.',
                'questions' => array_values(array_filter($selected, fn (array $item): bool => isset($item['question']))),
                'words' => array_values(array_filter($selected, fn (array $item): bool => isset($item['word']))),
                'reward_coins' => 25,
            ]);
        });

        if ($mission && $mission->wasRecentlyCreated) {
            $mission->loadMissing('student');
            NotificationSender::sendToUsers(
                [$mission->student],
                'practice_assigned',
                'New practice mission',
                $mission->title,
                route('student.practice.show', $mission),
            );
        }

        return $mission;
    }
}
