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
        $response->assertSeeText('Practice Wardrobe');
        $response->assertSeeText('0 rewards unlocked');
        $response->assertDontSeeText('No Reward Data');
        $response->assertSeeText('Performance Breakdown');
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
}
