<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_support_placeholder(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('support.developing'))
            ->assertOk()
            ->assertSee('This system is currently developing')
            ->assertSee('Back to Dashboard');
    }

    public function test_guest_is_redirected_from_support_page(): void
    {
        $this->get(route('support.developing'))
            ->assertRedirect(route('login'));
    }
}