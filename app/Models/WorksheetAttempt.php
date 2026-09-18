<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorksheetAttempt extends Model
{
    protected $fillable = ['assessment_id', 'user_id', 'progress_id', 'pages', 'total', 'score', 'feedback', 'reviewed_at'];
    protected $casts = ['pages' => 'array', 'reviewed_at' => 'datetime', 'total' => 'integer', 'score' => 'integer'];

    public function assessment(): BelongsTo { return $this->belongsTo(Assessment::class); }
    public function student(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function progress(): BelongsTo { return $this->belongsTo(AssessmentProgress::class, 'progress_id'); }
}
