<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiRecommendationEvaluation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'metrics' => 'array',
        'dataset_info' => 'array',
        'evaluated_at' => 'datetime',
    ];

    public function requester(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
