<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherStudentPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_and_roster_are_distinct_pages(): void
    {
        $teacher = User::factory()->teacher()->create(['section' => 'Section B']);
        $student = User::factory()->create(['name' => 'Roster Learner']);
        $this->actingAs($teacher);
        $list = $this->get(route('students.index'))->assertOk()->assertViewIs('students.index')
            ->assertSeeText('Student Roster')->assertSeeText($student->name)
            ->assertSee('href="'.route('students.create').'"', false)
            ->assertDontSee('id="add-student"', false)->assertDontSee('id="student-password"', false);
        $create = $this->get(route('students.create'))->assertOk()->assertViewIs('students.create')
            ->assertSee('action="'.route('students.store').'"', false)
            ->assertSee('id="student-first-name"', false)->assertSeeText('Section B (my section)')
            ->assertSeeText('Cancel')->assertDontSeeText('Student Roster')->assertDontSeeText($student->name);
        if (getenv('CAPTURE_STUDENT_PAGES')) {
            file_put_contents(storage_path('app/student-list-fixture.html'), $list->getContent());
            file_put_contents(storage_path('app/student-create-fixture.html'), $create->getContent());
        }
    }

    public function test_create_page_requires_teacher_login(): void
    {
        $this->get(route('students.create'))->assertRedirect(route('login'));
        foreach ([User::factory()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get(route('students.create'))->assertForbidden();
        }
    }

    public function test_validation_returns_to_form_with_safe_inputs_preserved(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher)->from(route('students.create'))->post(route('students.store'), [
            'first_name' => 'New', 'last_name' => 'Learner', 'email' => 'not-an-email',
            'gender' => 'female', 'section' => 'Section B', 'password' => 'secret-pass', 'password_confirmation' => 'mismatch',
        ])->assertRedirect(route('students.create'))->assertSessionHasErrors(['email', 'password']);
        $this->get(route('students.create'))->assertOk()->assertSee('value="New"', false)
            ->assertSee('value="not-an-email"', false)->assertDontSee('value="secret-pass"', false);
        $this->assertDatabaseMissing('users', ['name' => 'New Learner']);
    }

    public function test_search_points_to_the_separate_destinations(): void
    {
        $this->actingAs(User::factory()->teacher()->create());
        $this->get(route('teacher.search', ['q' => 'add student']))->assertOk()
            ->assertViewHas('quickLinks', fn ($links) => $links->count() === 1 && $links->first()['href'] === route('students.create'));
        $this->get(route('teacher.search', ['q' => 'roster']))->assertOk()
            ->assertViewHas('quickLinks', fn ($links) => $links->count() === 1 && $links->first()['href'] === route('students.index'));
    }
}
