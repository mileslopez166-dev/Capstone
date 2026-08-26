<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subject',
        'quiz_type',
        'delivery_method',
        'target_section',
        'focus_areas',
        'asset_path',
        'manual_questions',
        'instructions',
        'status',
        'created_by',
    ];

    protected $casts = [
        'focus_areas' => 'array',
        'manual_questions' => 'array',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
