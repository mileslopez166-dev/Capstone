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
        'instructions',
        'status',
        'created_by',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
