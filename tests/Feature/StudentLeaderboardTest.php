<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_all_section_leaderboard_ranked_by_points(): void
    {
        $teacher = User::factory()->teacher()->create();
        $currentStudent = User::factory()->create([
            'name' => 'Miles Lopez',
            'section' => 'Section A',
            'approval_status' => 'approved',
        ]);
        $topStudent = User::factory()->create([
            'name' => 'Lyra Vale',
            'section' => 'Section B',
            'approval_status' => 'approved',
            'gender' => 'female',
        ]);

        $assessment = $this->assessment($teacher);
        $this->submission($assessment, $currentStudent, 250, 1, 2);
        $this->submission($assessment, $topStudent, 500, 2, 2);

        $response = $this->actingAs($currentStudent)->get(route('student.leaderboard'));

        $response
            ->assertOk()
            ->assertSeeText('All Sections Rankings')
            ->assertSeeText('Lyra Vale')
            ->assertSeeText('Miles Lopez')
            ->assertSee('images/campus/student-girl.png', false)
            ->assertSeeText('#2')
            ->assertSee('href="'.route('student.leaderboard').'"', false);
    }

    public function test_student_can_filter_leaderboard_by_section(): void
    {
        $teacher = User::factory()->teacher()->create();
        $sectionAStudent = User::factory()->create([
            'name' => 'Nova Finch',
            'section' => 'Section A',
            'approval_status' => 'approved',
        ]);
        $sectionBStudent = User::factory()->create([
            'name' => 'Rowan Moss',
            'section' => 'Section B',
            'approval_status' => 'approved',
        ]);

        $assessment = $this->assessment($teacher);
        $this->submission($assessment, $sectionAStudent, 250, 1, 2);
        $this->submission($assessment, $sectionBStudent, 500, 2, 2);

        $response = $this->actingAs($sectionAStudent)->get(route('student.leaderboard', ['scope' => 'section_a']));

        $response
            ->assertOk()
            ->assertSeeText('Section A Rankings')
            ->assertSeeText('Nova Finch')
            ->assertDontSeeText('Rowan Moss');
    }

    public function test_leaderboard_shows_rank_tier_effect_labels(): void
    {
        $teacher = User::factory()->teacher()->create();
        $students = collect([
            ['name' => 'Top Reader', 'points' => 1500],
            ['name' => 'Second Reader', 'points' => 1250],
            ['name' => 'Third Reader', 'points' => 1000],
            ['name' => 'Fourth Reader', 'points' => 750],
            ['name' => 'Fifth Reader', 'points' => 500],
            ['name' => 'Sixth Reader', 'points' => 250],
        ])->map(fn (array $entry) => [
            'student' => User::factory()->create([
                'name' => $entry['name'],
                'section' => 'Section A',
                'approval_status' => 'approved',
            ]),
            'points' => $entry['points'],
        ]);

        $assessment = $this->assessment($teacher);
        $students->each(fn (array $entry) => $this->submission($assessment, $entry['student'], $entry['points'], 1, 1));

        $this->actingAs($students->last()['student'])
            ->get(route('student.leaderboard'))
            ->assertOk()
            ->assertSeeText('Flaming')
            ->assertSeeText('Diamond')
            ->assertSeeText('Platinum')
            ->assertSeeText('Gold')
            ->assertSeeText('Silver')
            ->assertSeeText('Bronze');
    }
    public function test_non_student_can_not_view_student_leaderboard(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->get(route('student.leaderboard'))
            ->assertForbidden();
    }

    private function assessment(User $teacher): Assessment
    {
        return Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Leaderboard Mission',
            'subject' => 'literacy',
            'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual',
            'target_section' => 'all',
            'assessment_type' => 'silent_reading',
            'focus_areas' => ['Reading Fluency'],
            'instructions' => 'Answer carefully.',
            'status' => 'published',
            'manual_questions' => [],
        ]);
    }

    private function submission(Assessment $assessment, User $student, int $points, int $correct, int $questions): void
    {
        AssessmentSubmission::query()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'attempt_number' => 1,
            'answers' => [],
            'correct_count' => $correct,
            'question_count' => $questions,
            'points' => $points,
            'possible_points' => 500,
            'submitted_at' => now(),
        ]);
    }
}
