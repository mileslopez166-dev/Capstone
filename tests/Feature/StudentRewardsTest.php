<?php

namespace Tests\Feature;

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
        $response->assertSeeText('No Rewards Yet');
        $response->assertSeeText('Performance Breakdown');
    }
}
