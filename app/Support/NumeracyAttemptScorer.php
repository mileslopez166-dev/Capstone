<?php

namespace App\Support;

use App\Models\AssessmentProgress;
use App\Models\AssessmentSubmission;
use App\Models\User;
use App\Models\WorksheetAttempt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class NumeracyAttemptScorer
{
    public function score(WorksheetAttempt $attempt): ?AssessmentSubmission
    {
        $created = false;

        $submission = DB::transaction(function () use ($attempt, &$created): ?AssessmentSubmission {
            User::query()->whereKey($attempt->user_id)->lockForUpdate()->firstOrFail();

            $attempt = WorksheetAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            $progress = AssessmentProgress::query()->lockForUpdate()->findOrFail($attempt->progress_id);

            if ($progress->submission_id) {
                $submission = AssessmentSubmission::query()->find($progress->submission_id);
                if ($submission && ! $attempt->reviewed_at) {
                    $attempt->update([
                        'total' => $submission->question_count,
                        'score' => $submission->correct_count,
                        'reviewed_at' => now(),
                    ]);
                }

                return $submission;
            }

            if ($attempt->reviewed_at) {
                return null;
            }

            $assessment = $attempt->assessment()->first();
            if (! $assessment?->worksheet_number) {
                return null;
            }

            $grading = NumeracyWorksheetGrader::grade(
                NumeracyWorksheets::find($assessment->worksheet_number),
                $attempt->pages ?? []
            );

            if (! $grading) {
                return null;
            }

            $submission = AssessmentSubmission::query()
                ->where('assessment_id', $attempt->assessment_id)
                ->where('user_id', $attempt->user_id)
                ->where('attempt_number', $progress->attempt_number)
                ->first();

            if (! $submission) {
                $pointsPerCorrectAnswer = (int) config('gamification.points_per_correct_answer');
                $submission = AssessmentSubmission::create([
                    'assessment_id' => $attempt->assessment_id,
                    'user_id' => $attempt->user_id,
                    'attempt_number' => $progress->attempt_number,
                    'answers' => array_column($grading['items'], 'student_answer'),
                    'correct_count' => $grading['correct'],
                    'question_count' => $grading['total'],
                    'points' => $grading['correct'] * $pointsPerCorrectAnswer,
                    'possible_points' => $grading['total'] * $pointsPerCorrectAnswer,
                    'submitted_at' => $attempt->created_at,
                ]);
                $created = true;
            }

            $progress->update(['submission_id' => $submission->id]);
            $attempt->update([
                'total' => $submission->question_count,
                'score' => $submission->correct_count,
                'reviewed_at' => now(),
            ]);
            AssessmentCoinRewards::award($submission);

            return $submission;
        });

        if ($submission && $created) {
            NotificationSender::sendToUsers(
                [$submission->student],
                'worksheet_graded',
                'Your worksheet was checked automatically',
                $submission->assessment?->title,
                route('worksheets.review', $attempt)
            );

            try {
                app(MLPredictionService::class)->predictForSubmission($submission);
                app(LearningRecommender::class)->recommend($submission->student, $submission);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $submission;
    }

    public function backfillForStudent(User $student): array
    {
        return $this->backfill(
            WorksheetAttempt::query()->where('user_id', $student->id)->whereNull('reviewed_at')
        );
    }

    public function backfillForTeacher(User $teacher): array
    {
        return $this->backfill(
            WorksheetAttempt::query()
                ->whereNull('reviewed_at')
                ->whereHas('assessment', fn (Builder $query) => $query->where('created_by', $teacher->id))
        );
    }

    public function backfill(?Builder $query = null): array
    {
        $summary = ['checked' => 0, 'scored' => 0, 'manual' => 0, 'failed' => 0];
        $query ??= WorksheetAttempt::query()->whereNull('reviewed_at');

        $query->with(['assessment', 'progress'])->eachById(function (WorksheetAttempt $attempt) use (&$summary): void {
            $summary['checked']++;

            try {
                $this->score($attempt) ? $summary['scored']++ : $summary['manual']++;
            } catch (Throwable $exception) {
                $summary['failed']++;
                report($exception);
            }
        });

        return $summary;
    }
}
