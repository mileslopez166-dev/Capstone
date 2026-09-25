<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorTurn extends Model
{
    protected $fillable = ['request_id', 'question', 'answer'];

    protected $casts = ['question' => 'encrypted', 'answer' => 'encrypted'];

    protected $hidden = ['request_id'];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(TutorChat::class, 'tutor_chat_id');
    }
}
