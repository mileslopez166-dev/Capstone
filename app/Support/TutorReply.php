<?php

namespace App\Support;

class TutorReply
{
    public function __construct(public string $answer, public ?string $teacherAttentionReason = null)
    {
    }
}
