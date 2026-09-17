<?php

namespace App\Support;

use App\Models\AssessmentProgress;
use App\Models\PracticeMission;
use App\Models\User;
use Illuminate\Support\Collection;

class StudentTaskQueue
{
    public static function assessments(Collection $assessments, User $student): Collection
    {
        $progress = AssessmentProgress::query()->where('user_id', $student->id)
            ->whereIn('assessment_id', $assessments->pluck('id'))->whereNull('submission_id')
            ->latest('updated_at')->get();

        return $assessments->map(function ($assessment) use ($progress) {
            $saved = $progress->first(fn ($entry) => (int) $entry->assessment_id === (int) $assessment->id
                && (int) $entry->attempt_number === (int) $assessment->student_attempts_count + 1 && ! empty($entry->state));
            $assessment->student_state = $saved ? 'in_progress' : ($assessment->student_attempts_count ? 'completed' : 'not_started');
            $assessment->student_action = $saved ? 'Continue Assessment' : ($assessment->student_attempts_count ? 'Retake Assessment' : 'Start Assessment');
            $assessment->student_progress_at = $saved?->updated_at;

            return $assessment;
        });
    }

    public static function next(Collection $assessments, User $student): ?array
    {
        $tasks = $assessments->map(fn ($assessment) => [
            'title' => $assessment->title, 'kind' => 'Assessment',
            'status' => $assessment->student_state, 'action' => $assessment->student_action,
            'url' => route('student.assessments.show', $assessment),
            'priority' => match ($assessment->student_state) { 'in_progress' => 0, 'not_started' => 2, default => 4 },
            'updated' => $assessment->student_progress_at?->timestamp ?? 0,
        ]);
        $missions = PracticeMission::query()->where('student_id', $student->id)->where('status', 'assigned')->get();
        foreach ($missions as $mission) {
            $started = ! empty($mission->progress);
            $tasks->push([
                'title' => $mission->title, 'kind' => 'Practice Mission',
                'status' => $started ? 'in_progress' : 'not_started',
                'action' => $started ? 'Continue Practice' : 'Start Practice',
                'url' => route('student.practice.show', $mission),
                'priority' => $started ? 1 : 3, 'updated' => $mission->updated_at->timestamp,
            ]);
        }

        return $tasks->sortBy([['priority', 'asc'], ['updated', 'desc']])->first();
    }
}
