<?php

namespace Tests\Unit;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Support\MLPredictionService;
use App\Support\PhilIri;
use PHPUnit\Framework\TestCase;

class MLPredictionFeaturesTest extends TestCase
{
    public function test_oral_reading_uses_wrp_and_leaves_comprehension_missing(): void
    {
        $assessment = new Assessment([
            'subject' => 'literacy',
            'assessment_type' => 'oral_reading',
            'story_description' => implode(' ', array_fill(0, 104, 'word')),
        ]);
        $submission = new AssessmentSubmission([
            'attempt_number' => 1,
            'correct_count' => 0,
            'question_count' => 0,
            'phil_iri' => PhilIri::initial($assessment, 0, 0, [
                'word_marks' => array_merge(array_fill(0, 19, 2), array_fill(0, 85, 0)),
            ]),
        ]);
        $submission->setRelation('assessment', $assessment);

        $method = new \ReflectionMethod(MLPredictionService::class, 'assessmentFeatures');
        $method->setAccessible(true);
        $features = $method->invoke(new MLPredictionService, $submission);

        $this->assertSame(81.73, $features['reading_score']);
        $this->assertSame(81.73, $features['reading_accuracy']);
        $this->assertNull($features['comprehension_score']);
        $this->assertNull($features['listening_score']);
    }
}
