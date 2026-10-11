<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiModelEvaluation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'dataset_info' => 'array',
        'comparison_metrics' => 'array',
        'final_test_metrics' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AiModelVersion::class, 'evaluation_id');
    }
}
