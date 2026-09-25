<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MLPrediction extends Model
{
    protected $table = 'ml_predictions';

    protected $fillable = [
        'assessment_submission_id',
        'student_id',
        'model_name',
        'prediction',
        'confidence_score',
        'input_data',
        'recommendation',
    ];

    protected $casts = [
        'confidence_score' => 'float',
        'input_data' => 'array',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssessmentSubmission::class, 'assessment_submission_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
