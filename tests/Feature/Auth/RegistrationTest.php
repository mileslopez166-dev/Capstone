<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'middle_name' => 'Middle',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'role' => 'teacher',
            'section' => 'Section A',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('users', [
            'name' => 'Test Middle User',
            'email' => 'test@example.com',
            'role' => 'teacher',
            'section' => 'Section A',
            'approval_status' => 'pending',
        ]);
    }

    public function test_student_registration_redirects_to_the_login_screen(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Student',
            'middle_name' => '',
            'last_name' => 'User',
            'email' => 'student@example.com',
            'role' => 'student',
            'section' => 'Section B',
            'gender' => 'female',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('users', [
            'name' => 'Student User',
            'email' => 'student@example.com',
            'role' => 'student',
            'gender' => 'female',
            'approval_status' => 'pending',
        ]);
    }

    public function test_admin_role_can_not_be_self_registered(): void
    {
        $response = $this->from('/register')->post('/register', [
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'section' => 'Admin-Section',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', [
            'email' => 'admin@example.com',
        ]);
    }

    public function test_teacher_registration_no_longer_requires_an_access_code(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Teacher',
            'last_name' => 'User',
            'email' => 'teacher@example.com',
            'role' => 'teacher',
            'section' => 'Section C',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('users', [
            'name' => 'Teacher User',
            'email' => 'teacher@example.com',
            'section' => 'Section C',
            'approval_status' => 'pending',
        ]);
    }
}