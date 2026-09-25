<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutorChat extends Model
{
    protected $fillable = ['user_id', 'subject', 'assessment_submission_id', 'teacher_help_requested_at'];

    protected $casts = ['teacher_help_requested_at' => 'datetime'];

    public function turns(): HasMany
    {
        return $this->hasMany(TutorTurn::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssessmentSubmission::class, 'assessment_submission_id');
    }

    public function title(): string
    {
        return $this->submission?->assessment?->title ?? ucfirst($this->subject).' questions';
    }
}
