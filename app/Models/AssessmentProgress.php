<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AssessmentProgress extends Model
{
    protected $table = 'assessment_progress';

    protected $fillable = [
        'assessment_id', 'user_id', 'attempt_number', 'attempt_key',
        'state', 'revision', 'submission_id',
    ];

    protected $casts = ['state' => 'array', 'revision' => 'integer', 'multiplication_table_unlocked_at' => 'datetime'];

    public static function forAttempt(Assessment $assessment, User $student, int $attemptNumber): self
    {
        return static::query()->firstOrCreate([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'attempt_number' => $attemptNumber,
        ], [
            'attempt_key' => (string) Str::uuid(),
            'state' => [],
        ]);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssessmentSubmission::class, 'submission_id');
    }

    public function recordAssistance(User $actor): void
    {
        if ($actor->isTeacher()) {
            $this->forceFill(['administered_by' => $actor->id])->save();
        }
    }

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by')->withTrashed();
    }
}
