<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Support\HtmlString;
use Tests\TestCase;

class StudentGardenTest extends TestCase
{
    public function test_student_pages_include_a_decorative_garden(): void
    {
        foreach (['student.dashboard', 'student.activities', 'student.rewards', 'student.leaderboard', 'student.practice.index'] as $name) {
            $this->assertStringContainsString('class="student-garden-canvas" aria-hidden="true"', $this->renderLayout('student', $name));
        }
    }

    public function test_assessment_and_practice_sessions_exclude_the_garden(): void
    {
        foreach (['student.assessments.show', 'student.practice.show'] as $name) {
            $this->assertStringNotContainsString('student-garden-canvas', $this->renderLayout('student', $name));
        }
    }

    public function test_staff_pages_exclude_the_student_garden(): void
    {
        foreach (['teacher', 'admin'] as $role) {
            $this->assertStringNotContainsString('student-garden-canvas', $this->renderLayout($role, $role.'.dashboard'));
        }
    }

    private function renderLayout(string $role, string $routeName): string
    {
        $this->withoutVite();
        $this->actingAs((new User)->forceFill(['id' => 1, 'role' => $role]));
        request()->setRouteResolver(fn () => (new Route('GET', '/', fn () => null))->name($routeName)->bind(request()));

        return view('layouts.app', ['slot' => new HtmlString('<main>Preview</main>')])->render();
    }
}
