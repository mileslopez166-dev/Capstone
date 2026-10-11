<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiModelVersion extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'metrics' => 'array',
        'provenance' => 'array',
        'validated_at' => 'datetime',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(AiModelEvaluation::class, 'evaluation_id');
    }
}
