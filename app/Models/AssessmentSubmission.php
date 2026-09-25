<?php

namespace App\Models;

use App\Support\AssessmentScores;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentSubmission extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::created(fn (self $submission) => \App\Support\InterventionFollowUp::recordSubmission($submission));
    }

    protected $fillable = [
        'assessment_id',
        'user_id',
        'attempt_number',
        'answers',
        'correct_count',
        'question_count',
        'points',
        'possible_points',
        'phil_iri',
        'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'phil_iri' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function coinReward(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PracticeCoinTransaction::class);
    }

    public function interventionPlan(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(InterventionPlan::class);
    }

    public function mlPrediction(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MLPrediction::class);
    }

    public function worksheetAttempt(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(WorksheetAttempt::class, AssessmentProgress::class, 'submission_id', 'progress_id', 'id', 'id');
    }

    public function scorePercentage(): ?int
    {
        return AssessmentScores::percentage($this);
    }
}
