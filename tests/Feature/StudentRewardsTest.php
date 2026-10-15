<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentRewardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_rewards_page_requires_authentication(): void
    {
        $response = $this->get(route('student.rewards'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_the_student_rewards_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Mateo Cruz',
        ]);

        $response = $this->actingAs($user)->get(route('student.rewards'));

        $response->assertOk();
        $response->assertSeeText('Rewards');
        $response->assertSeeText('Wardrobe');
        $response->assertSeeText('0 rewards unlocked');
        $response->assertDontSeeText('No Reward Data');
        $response->assertSeeText('Performance Breakdown');
        $response->assertSeeText('Leaderboard Status');
        $response->assertSeeText('Unranked Tier');
    }

    public function test_rewards_show_real_coins_and_owned_items(): void
    {
        $student = User::factory()->create();
        $student->practiceCoinTransactions()->create(['amount' => 50, 'description' => 'Practice rewards']);
        $student->practiceCoinTransactions()->create(['amount' => -25, 'item_key' => 'headwear:star_cap', 'description' => 'Star cap']);

        $this->actingAs($student)->get(route('student.rewards'))->assertOk()
            ->assertSeeText('25 coins')->assertSeeText('1 rewards unlocked')
            ->assertSee(route('student.wardrobe.edit'));
    }

    public function test_rewards_show_overall_and_section_leaderboard_status(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['section' => 'Matapat', 'approval_status' => 'approved']);
        $leader = User::factory()->create(['section' => 'Magalang', 'approval_status' => 'approved']);
        $assessment = Assessment::create([
            'created_by' => $teacher->id,
            'title' => 'Leaderboard Rewards Check',
            'subject' => 'literacy',
            'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual',
            'target_section' => 'all',
            'status' => 'published',
            'manual_questions' => [],
        ]);
        foreach ([[$student, 250, 1], [$leader, 500, 2]] as [$participant, $points, $correct]) {
            AssessmentSubmission::create([
                'assessment_id' => $assessment->id,
                'user_id' => $participant->id,
                'attempt_number' => 1,
                'answers' => [],
                'correct_count' => $correct,
                'question_count' => 2,
                'points' => $points,
                'possible_points' => 500,
                'submitted_at' => now(),
            ]);
        }

        $this->actingAs($student)->get(route('student.rewards'))->assertOk()
            ->assertViewHas('leaderboardStatus', fn (array $status): bool => $status['rank'] === 2
                && $status['section_rank'] === 1
                && $status['tier']['label'] === 'Diamond'
                && $status['points'] === 250
                && $status['completed'] === 1
                && $status['accuracy'] === 50)
            ->assertSeeText('Diamond Tier')
            ->assertSeeText('Section Rank #1')
            ->assertSeeText('Matapat')
            ->assertSee(route('student.leaderboard'), false);
    }
}
