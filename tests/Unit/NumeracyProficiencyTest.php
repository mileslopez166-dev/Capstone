<?php

namespace Tests\Unit;

use App\Support\NumeracyProficiency;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NumeracyProficiencyTest extends TestCase
{
    #[DataProvider('scoreBands')]
    public function test_score_boundaries_have_documented_operational_labels(?int $score, ?string $label): void
    {
        $this->assertSame($label, NumeracyProficiency::forPercentage($score));
    }

    public static function scoreBands(): array
    {
        return [
            'missing' => [null, null],
            'below range' => [-1, null],
            'beginning lower' => [0, 'Beginning'],
            'beginning upper' => [59, 'Beginning'],
            'developing lower' => [60, 'Developing'],
            'developing upper' => [74, 'Developing'],
            'proficient lower' => [75, 'Proficient'],
            'proficient upper' => [89, 'Proficient'],
            'advanced lower' => [90, 'Advanced'],
            'advanced upper' => [100, 'Advanced'],
            'above range' => [101, null],
        ];
    }
}
