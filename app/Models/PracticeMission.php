<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PracticeMission extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'questions' => 'array', 'words' => 'array', 'progress' => 'array',
        'word_reviews' => 'array', 'completed_at' => 'datetime', 'reviewed_at' => 'datetime',
    ];

    public function displayStatus(bool $forTeacher = false): string
    {
        if ($this->status === 'assigned') {
            return empty($this->progress) ? 'not_started' : 'in_progress';
        }
        if ($forTeacher && $this->status === 'completed') {
            return $this->reviewed_at ? 'reviewed' : 'needs_review';
        }

        return $this->status;
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PracticeAttempt::class);
    }
}
