<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentSubmission extends Model
{
    use HasFactory;

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

    public function worksheetAttempt(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(WorksheetAttempt::class, AssessmentProgress::class, 'submission_id', 'progress_id', 'id', 'id');
    }
}
