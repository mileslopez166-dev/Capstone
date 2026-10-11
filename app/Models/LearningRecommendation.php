<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningRecommendation extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['score' => 'float', 'reasons' => 'array'];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function sourceSubmission(): BelongsTo
    {
        return $this->belongsTo(AssessmentSubmission::class, 'source_submission_id');
    }
}
