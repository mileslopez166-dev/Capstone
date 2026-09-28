<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentAnswerFeedback extends Model
{
    protected $table = 'assessment_answer_feedback';

    protected $fillable = ['assessment_submission_id', 'question_index', 'source_hash', 'answer'];

    protected $casts = ['question_index' => 'integer', 'answer' => 'encrypted'];
}
