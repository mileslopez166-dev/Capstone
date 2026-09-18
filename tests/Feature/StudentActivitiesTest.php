<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentActivitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_activities_page_requires_authentication(): void
    {
        $response = $this->get(route('student.activities'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_the_student_activities_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Mateo Cruz',
        ]);

        $response = $this->actingAs($user)->get(route('student.activities'));

        $response->assertOk();
        $response->assertSeeText('Literacy')->assertSeeText('Numeracy');
        $response->assertSee(route('student.activities', ['subject' => 'literacy']), false);
        $response->assertSee(route('student.activities', ['subject' => 'numeracy']), false);
        $response->assertDontSeeText('Pending Assessments');
        $this->get(route('student.activities', ['subject' => 'literacy']))->assertOk()->assertSeeText('No pending assessment');
    }


    public function test_student_can_review_wrong_answers_from_recorded_outputs(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create([
            'name' => 'Miles Lopez',
            'section' => 'Section A',
            'approval_status' => 'approved',
        ]);

        $assessment = Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Apple Story Quiz',
            'subject' => 'literacy',
            'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual',
            'target_section' => 'section_a',
            'assessment_type' => 'silent_reading',
            'focus_areas' => ['Comprehension Depth'],
            'instructions' => 'Answer carefully.',
            'status' => 'published',
            'manual_questions' => [[
                'question' => 'What is an apple?',
                'answers' => ['A' => 'Fruit', 'B' => 'Color', 'C' => 'Thing', 'D' => 'Person'],
                'correct_answer' => 'A',
            ]],
        ]);

        AssessmentSubmission::query()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'attempt_number' => 1,
            'answers' => [0 => 'B'],
            'correct_count' => 0,
            'question_count' => 1,
            'points' => 0,
            'possible_points' => 250,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($student)->get(route('student.activities'));

        $response
            ->assertOk()
            ->assertSeeText('Assessment Review')
            ->assertSee('<details class="group mt-5', false)
            ->assertSeeText('What is an apple?')
            ->assertSeeText('B. Color')
            ->assertSeeText('A. Fruit')
            ->assertSeeText('1 Wrong');
    }
    public function test_student_activities_page_lists_published_teacher_assessments(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();

        Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Reading Fluency Check',
            'subject' => 'literacy',
            'instructions' => 'Read the passage before answering.',
            'status' => 'published',
        ]);

        Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Fractions Drill',
            'subject' => 'numeracy',
            'instructions' => 'Solve the number problems carefully.',
            'status' => 'published',
        ]);

        $this->actingAs($student)->get(route('student.activities'))->assertOk()
            ->assertDontSeeText('Reading Fluency Check')->assertDontSeeText('Fractions Drill');
        $this->get(route('student.activities', ['subject' => 'literacy']))->assertOk()
            ->assertSeeText('Pending assessments')->assertSeeText('Reading Fluency Check')
            ->assertDontSeeText('Fractions Drill')->assertDontSeeText('Your Numeracy Mission');
        $this->get(route('student.activities', ['subject' => 'numeracy']))->assertOk()
            ->assertSeeText('Fractions Drill')->assertDontSeeText('Reading Fluency Check')
            ->assertSeeText('Your Numeracy Mission')->assertSee(route('worksheets.mission'), false);
    }

    public function test_assessment_types_appear_beside_titles_in_pending_and_recorded_activities(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $types = [
            'oral_reading' => 'Oral Reading Assessment',
            'silent_reading' => 'Silent Reading Assessment',
            'listening_comprehension' => 'Listening Comprehension Assessment',
            'group_screening' => 'Group Screening Test',
        ];
        $records = [];
        foreach ($types as $type => $label) {
            foreach (['Pending', 'Recorded'] as $state) {
                $assessment = Assessment::create([
                    'created_by' => $teacher->id, 'title' => $state.' '.$type.' story', 'subject' => 'literacy',
                    'assessment_type' => $type, 'quiz_type' => 'multiple_choice', 'status' => 'published',
                    'manual_questions' => [],
                ]);
                if ($state === 'Recorded') {
                    $submission = AssessmentSubmission::create([
                        'assessment_id' => $assessment->id, 'user_id' => $student->id, 'attempt_number' => 1,
                        'answers' => [], 'correct_count' => 0, 'question_count' => 0, 'points' => 0,
                        'possible_points' => 0, 'submitted_at' => now(),
                    ]);
                    $records[$type] = $submission->id;
                }
            }
        }

        $response = $this->actingAs($student)->get(route('student.activities', ['subject' => 'literacy']))->assertOk();
        $dom = new \DOMDocument();
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        foreach ($types as $type => $label) {
            $nodes = $xpath->query('//p[@data-assessment-type="'.$type.'"]');
            $this->assertCount(2, $nodes);
            foreach ($nodes as $node) {
                $this->assertStringContainsString($label, $node->textContent);
                $this->assertStringContainsString($type.' story', $node->parentNode->textContent);
            }
            $this->assertCount(1, $xpath->query('//div[@id="result-'.$records[$type].'"]//p[@data-assessment-type="'.$type.'"]'));
        }
        $oralCard = $xpath->query('//a[.//p[@data-assessment-type="oral_reading"]]')->item(0);
        $this->assertNotNull($oralCard);
        $this->assertStringNotContainsString('Multiple Choice', $oralCard->textContent);
    }

    public function test_numeracy_is_not_labelled_as_reading_and_default_literacy_uses_silent_reading(): void
    {
        $teacher = User::factory()->teacher()->create();
        foreach (['numeracy', 'literacy'] as $subject) {
            Assessment::create([
                'created_by' => $teacher->id, 'title' => ucfirst($subject).' activity',
                'subject' => $subject, 'status' => 'published',
            ]);
        }
        $this->actingAs(User::factory()->create())->get(route('student.activities', ['subject' => 'literacy']))->assertOk()
            ->assertSeeText('Silent Reading Assessment')->assertDontSeeText('Numeracy activity')->assertDontSeeText('Oral Reading Assessment');
        $this->get(route('student.activities', ['subject' => 'numeracy']))->assertOk()
            ->assertSeeText('Numeracy Assessment')->assertDontSeeText('Silent Reading Assessment');
    }

    public function test_subject_counts_and_queues_only_include_available_section_assessments(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['section' => 'Section A']);
        foreach (['literacy', 'numeracy'] as $subject) {
            foreach (['Available', 'Locked', 'Other section', 'Exhausted'] as $state) {
                $assessment = Assessment::create(['created_by' => $teacher->id, 'title' => $state.' '.$subject,
                    'subject' => $subject, 'status' => $state === 'Locked' ? 'draft' : 'published',
                    'target_section' => $state === 'Other section' ? 'section_b' : 'section_a', 'retry_limit' => 0]);
                if ($state === 'Exhausted') {
                    AssessmentSubmission::create(['assessment_id' => $assessment->id, 'user_id' => $student->id,
                        'answers' => [], 'correct_count' => 1, 'question_count' => 1, 'points' => 250,
                        'possible_points' => 250, 'submitted_at' => now()]);
                }
            }
        }
        foreach (['literacy', 'numeracy'] as $subject) {
            $this->actingAs($student)->get(route('student.activities', ['subject' => $subject]))->assertOk()
                ->assertViewHas('subjectCounts', fn ($counts) => $counts->get('literacy') === 1 && $counts->get('numeracy') === 1)
                ->assertViewHas('pendingAssessments', fn ($items) => $items->count() === 1 && $items->first()->title === 'Available '.$subject)
                ->assertViewHas('completedSubmissions', fn ($items) => $items->count() === 1 && $items->first()->assessment->subject === $subject)
                ->assertDontSeeText('Locked '.$subject)->assertDontSeeText('Other section '.$subject);
        }
        $this->get(route('student.activities'))->assertViewHas('completedSubmissions', fn ($items) => $items->count() === 2);
    }

    public function test_invalid_subjects_return_to_the_chooser_and_other_roles_cannot_use_it(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['science', ['literacy']] as $subject) {
            $this->get(route('student.activities', ['subject' => $subject]))->assertRedirect(route('student.activities'));
        }
        foreach (['teacher', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get(route('student.activities', ['subject' => 'numeracy']))->assertForbidden();
        }
    }
}
