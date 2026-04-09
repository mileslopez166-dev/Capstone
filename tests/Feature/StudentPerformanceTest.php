<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_performance_page_requires_authentication(): void
    {
        $student = User::factory()->create();

        $response = $this->get(route('students.show', $student));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_the_student_performance_page(): void
    {
        $user = User::factory()->teacher()->create();
        $student = User::factory()->create([
            'name' => 'Leo Stellaris',
        ]);

        $response = $this->actingAs($user)->get(route('students.show', $student));

        $response->assertOk();
        $response->assertSeeText('Leo');
        $response->assertSeeText('Stellaris');
        $response->assertSeeText('No completed activities yet');
    }
}
