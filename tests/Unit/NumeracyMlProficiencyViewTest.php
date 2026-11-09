<?php

namespace Tests\Unit;

use App\Models\AssessmentSubmission;
use App\Models\MLPrediction;
use Tests\TestCase;

class NumeracyMlProficiencyViewTest extends TestCase
{
    public function test_it_displays_the_saved_ml_numeracy_level_and_guidance(): void
    {
        $submission = new AssessmentSubmission(['correct_count' => 4, 'question_count' => 5]);
        $submission->setRelation('mlPrediction', new MLPrediction([
            'prediction' => 'Proficient',
            'confidence_score' => .87,
            'recommendation' => 'Practice mixed problem-solving activities.',
        ]));

        $html = view('components.numeracy-ml-proficiency', compact('submission'))->render();

        $this->assertStringContainsString('Numeracy Proficiency Level', $html);
        $this->assertStringContainsString('Proficient', $html);
        $this->assertStringContainsString('87% confidence', $html);
        $this->assertStringContainsString('Practice mixed problem-solving activities.', $html);
    }

    public function test_it_labels_the_official_score_band_when_ml_is_unavailable(): void
    {
        $submission = new AssessmentSubmission(['correct_count' => 2, 'question_count' => 5]);
        $submission->setRelation('mlPrediction', null);

        $html = view('components.numeracy-ml-proficiency', compact('submission'))->render();

        $this->assertStringContainsString('ML prediction unavailable', $html);
        $this->assertStringContainsString('Official score-band level', $html);
        $this->assertStringContainsString('Beginning', $html);
        $this->assertStringContainsString('not an ML prediction', $html);
    }
}
