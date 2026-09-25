<?php

namespace App\Support;

use RuntimeException;

class TutorUnavailable extends RuntimeException
{
    public function __construct(
        string $message = 'The tutor could not reply right now. Your question is still here. Please try again shortly.',
        public int $status = 503,
        public ?string $teacherAttentionReason = null,
    )
    {
        parent::__construct($message);
    }
}
