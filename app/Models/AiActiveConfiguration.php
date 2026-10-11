<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiActiveConfiguration extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['activated_at' => 'datetime'];
}
