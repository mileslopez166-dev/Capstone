<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PracticeAttempt extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['answers' => 'array', 'practiced_words' => 'array'];
}
