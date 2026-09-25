<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterventionPlan extends Model
{
    public const TYPES = [
        'enrichment' => 'Enrichment',
        'guided_practice' => 'Guided Practice',
        'remediation' => 'Remediation',
    ];

    public const STATUSES = [
        'planned' => 'Planned',
        'in_progress' => 'In Progress',
        'done' => 'Done',
    ];

    protected $fillable = ['type', 'notes', 'follow_up_date', 'status', 'comparison_basis'];

    protected $casts = ['follow_up_date' => 'date', 'follow_up_linked_at' => 'datetime'];

    public function followUpAssessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'follow_up_assessment_id');
    }

    public function followUpSubmission(): BelongsTo
    {
        return $this->belongsTo(AssessmentSubmission::class, 'follow_up_submission_id');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssessmentSubmission::class, 'assessment_submission_id');
    }
}
