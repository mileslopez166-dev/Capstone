<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    use HasFactory;

    public const UNLIMITED_RETRY_LIMIT = 65535;

    protected $fillable = [
        'title',
        'subject',
        'quiz_type',
        'delivery_method',
        'target_section',
        'assessment_type',
        'focus_areas',
        'asset_path',
        'manual_questions',
        'instructions',
        'story_title',
        'story_description',
        'status',
        'retry_limit',
        'created_by',
    ];

    protected $casts = [
        'retry_limit' => 'integer',
        'focus_areas' => 'array',
        'manual_questions' => 'array',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssessmentSubmission::class);
    }

    public static function retryLimitOptions(): array
    {
        $options = [];

        foreach (range(0, 10) as $limit) {
            $options[(string) $limit] = $limit === 0
                ? 'No retries'
                : $limit.' '.($limit === 1 ? 'retry' : 'retries');
        }

        $options['unlimited'] = 'Unlimited retries';

        return $options;
    }

    public static function normalizeRetryLimit(mixed $value): int
    {
        return $value === 'unlimited' ? self::UNLIMITED_RETRY_LIMIT : (int) $value;
    }

    public function hasUnlimitedRetries(): bool
    {
        return (int) $this->retry_limit >= self::UNLIMITED_RETRY_LIMIT;
    }

    public function retryLimitFormValue(): string
    {
        return $this->hasUnlimitedRetries() ? 'unlimited' : (string) (int) $this->retry_limit;
    }

    public function remainingIncludedAttempts(int $completedAttempts): int
    {
        if ($this->hasUnlimitedRetries()) {
            return PHP_INT_MAX;
        }

        return max(0, 1 + (int) $this->retry_limit - $completedAttempts);
    }
}
