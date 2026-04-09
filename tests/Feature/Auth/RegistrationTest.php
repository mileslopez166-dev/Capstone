<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.teacher_registration_code' => 'TEACHER2026']);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'teacher',
            'teacher_registration_code' => 'TEACHER2026',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'teacher',
        ]);
    }

    public function test_student_registration_redirects_to_the_login_screen(): void
    {
        $response = $this->post('/register', [
            'name' => 'Student User',
            'email' => 'student@example.com',
            'role' => 'student',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('users', [
            'email' => 'student@example.com',
            'role' => 'student',
        ]);
    }

    public function test_admin_role_can_not_be_self_registered(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', [
            'email' => 'admin@example.com',
        ]);
    }

    public function test_teacher_registration_requires_the_correct_access_code(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Teacher User',
            'email' => 'teacher@example.com',
            'role' => 'teacher',
            'teacher_registration_code' => 'WRONGCODE',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('teacher_registration_code');
        $this->assertDatabaseMissing('users', [
            'email' => 'teacher@example.com',
        ]);
    }
}
