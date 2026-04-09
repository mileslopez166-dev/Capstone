<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_authenticated_students_to_their_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('student.dashboard'));
    }

    public function test_root_redirects_authenticated_teachers_to_their_dashboard(): void
    {
        $user = User::factory()->teacher()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('teacher.dashboard'));
    }
}
