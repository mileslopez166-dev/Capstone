<?php

namespace Tests\Feature;

use App\Models\{Assessment, AssessmentProgress, User, WorksheetAttempt};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function assessment(User $teacher, array $attributes = []): Assessment
    {
        return $teacher->createdAssessments()->create(array_merge([
            'title' => 'Reading practice', 'subject' => 'literacy', 'status' => 'published', 'target_section' => 'all',
            'assessment_type' => 'oral_reading', 'manual_questions' => [], 'story_description' => 'We read together.',
        ], $attributes));
    }

    private function assertNavigation(TestResponse $response, string $role, string $href, ?string $group = null): void
    {
        $response->assertOk();
        $dom = new \DOMDocument();
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $menus = $xpath->query('//*[@data-role-navigation="'.$role.'"]');
        $this->assertSame(2, $menus->length, 'Desktop and mobile share the same menu.');
        $accordionNames = [];
        foreach ($menus as $menu) {
            $groups = $xpath->query('.//details[@data-navigation-group]', $menu);
            $name = $groups->item(0)->getAttribute('name');
            $this->assertNotEmpty($name);
            foreach ($groups as $details) $this->assertSame($name, $details->getAttribute('name'));
            $accordionNames[] = $name;
            $this->assertSame($group ? 1 : 0, $xpath->query('.//details[@open]', $menu)->length);
            $current = $xpath->query('.//a[@aria-current="page"]', $menu);
            $this->assertSame(1, $current->length);
            $this->assertSame($href, $current->item(0)->getAttribute('href'));
            if ($group) $this->assertSame(1, $xpath->query('.//details[@data-navigation-group="'.$group.'"][@open]', $menu)->length);
        }
        $this->assertCount(2, array_unique($accordionNames), 'Desktop and mobile accordions must open independently.');
    }

    public function test_related_teacher_pages_share_the_classroom_dropdown(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $this->actingAs($teacher);
        foreach ([
            ['teacher.dashboard', [], 'teacher.dashboard', null],
            ['teacher.ai-assistant.index', [], 'teacher.ai-assistant.index', null],
            ['students.index', [], 'students.index', 'classroom'],
            ['students.show', [$student], 'students.index', 'classroom'],
            ['reports.index', [], 'reports.index', 'classroom'],
            ['teacher.practice.index', [], 'teacher.practice.index', 'classroom'],
            ['profile.edit', [], 'profile.edit', null],
        ] as [$page, $params, $target, $group]) {
            $this->assertNavigation($this->get(route($page, $params)), 'teacher', route($target), $group);
        }
    }

    public function test_creation_and_created_assessments_are_distinct_pages(): void
    {
        $teacher = User::factory()->teacher()->create();
        $assessment = $this->assessment($teacher, ['title' => 'Previously created story']);
        $create = $this->actingAs($teacher)->get(route('assessments.create'));
        $create->assertViewIs('assessments.create')->assertSee('id="assessment-builder-form"', false)
            ->assertDontSeeText($assessment->title)->assertDontSee('assessment-list-item', false);
        $this->assertNavigation($create, 'teacher', route('assessments.create'), 'assessments');
        $index = $this->get(route('assessments.index'));
        $index->assertViewIs('assessments.index')->assertSeeText($assessment->title)
            ->assertDontSee('id="assessment-builder-form"', false)->assertDontSee('id="question-text-file"', false);
        $this->assertNavigation($index, 'teacher', route('assessments.index'), 'assessments');
        $this->assertNavigation($this->get(route('assessments.show', $assessment)), 'teacher', route('assessments.index'), 'assessments');
        $this->get(route('teacher.dashboard'))->assertSee('href="'.route('assessments.create').'"', false);
    }

    public function test_created_list_search_subject_filters_and_pagination_remain_private_to_teacher(): void
    {
        $teacher = User::factory()->teacher()->create();
        $foreign = $this->assessment(User::factory()->teacher()->create(), ['title' => 'Other teacher assessment']);
        for ($i = 0; $i < 13; $i++) $this->assessment($teacher, ['title' => 'Reading '.$i]);
        $worksheet = $this->assessment($teacher, ['title' => 'Division mission', 'subject' => 'numeracy', 'assessment_type' => 'worksheet', 'worksheet_number' => 1, 'worksheet_total' => 20]);
        $this->actingAs($teacher)->get(route('assessments.index'))->assertOk()
            ->assertViewHas('assessments', fn ($rows) => $rows->total() === 14 && $rows->count() === 12)
            ->assertDontSeeText($foreign->title);
        $this->get(route('assessments.index', ['subject' => 'literacy', 'page' => 2]))->assertOk()
            ->assertViewHas('assessments', fn ($rows) => $rows->total() === 13 && $rows->count() === 1);
        $this->get(route('assessments.index', ['subject' => 'numeracy']))->assertOk()
            ->assertViewHas('assessments', fn ($rows) => $rows->total() === 1 && $rows->first()->is($worksheet));
        $this->get(route('assessments.index', ['q' => 'Division']))->assertOk()->assertSeeText($worksheet->title)->assertDontSeeText('Reading 1');
        $this->get(route('assessments.index', ['q' => 'No match']))->assertOk()->assertSeeText('No matching assessments');
    }

    public function test_numeracy_library_and_reviews_have_their_own_pages_and_owner_scoping(): void
    {
        $teacher = User::factory()->teacher()->create();
        $other = User::factory()->teacher()->create();
        $student = User::factory()->create();
        foreach ([$teacher, $other] as $owner) {
            $assessment = $this->assessment($owner, ['subject' => 'numeracy', 'worksheet_number' => 1, 'worksheet_total' => 10]);
            $progress = AssessmentProgress::forAttempt($assessment, $student, 1);
            WorksheetAttempt::create(['assessment_id' => $assessment->id, 'user_id' => $student->id, 'progress_id' => $progress->id, 'pages' => [], 'total' => 10]);
        }
        $library = $this->actingAs($teacher)->get(route('worksheets.index'));
        $library->assertSeeText('Worksheet 4')->assertDontSeeText('Worksheet 35')->assertDontSee('id="assigned"', false)->assertDontSee('id="review"', false);
        $this->assertNavigation($library, 'teacher', route('assessments.create'), 'assessments');
        $reviews = $this->get(route('worksheets.reviews'));
        $reviews->assertViewHas('reviews', fn ($rows) => $rows->count() === 1 && $rows->first()->assessment->created_by === $teacher->id)
            ->assertDontSee('class="worksheet-library"', false);
        $this->assertNavigation($reviews, 'teacher', route('worksheets.reviews'), 'assessments');
        $this->assertNavigation($this->get(route('worksheets.create', 1)), 'teacher', route('assessments.create'), 'assessments');
    }

    public function test_students_get_dropdowns_with_the_correct_subject_and_account_page_selected(): void
    {
        $student = User::factory()->create();
        $this->actingAs($student);
        foreach ([
            ['student.dashboard', [], 'student.dashboard', [], null],
            ['student.activities', [], 'student.activities', [], 'activities'],
            ['student.activities', ['subject' => 'literacy'], 'student.activities', ['subject' => 'literacy'], 'activities'],
            ['student.activities', ['subject' => 'numeracy'], 'student.activities', ['subject' => 'numeracy'], 'activities'],
            ['worksheets.mission', [], 'worksheets.mission', [], 'activities'],
            ['student.practice.index', [], 'student.practice.index', [], 'activities'],
            ['student.leaderboard', [], 'student.leaderboard', [], 'progress'],
            ['student.rewards', [], 'student.rewards', [], 'progress'],
            ['profile.edit', [], 'profile.edit', [], 'account'],
            ['student.wardrobe.edit', [], 'student.wardrobe.edit', [], 'account'],
        ] as [$page, $params, $target, $targetParams, $group]) {
            $this->assertNavigation($this->get(route($page, $params)), 'student', route($target, $targetParams), $group);
        }
        $assessment = $this->assessment(User::factory()->teacher()->create());
        $this->assertNavigation($this->get(route('student.assessments.show', $assessment)), 'student', route('student.activities', ['subject' => 'literacy']), 'activities');
    }

    public function test_new_teacher_pages_reject_students_admins_and_guests(): void
    {
        foreach (['assessments.create', 'assessments.index', 'worksheets.reviews', 'teacher.ai-assistant.index'] as $page) $this->get(route($page))->assertRedirect(route('login'));
        foreach ([User::factory()->create(), User::factory()->admin()->create()] as $user) {
            foreach (['assessments.create', 'assessments.index', 'worksheets.reviews', 'teacher.ai-assistant.index'] as $page) $this->actingAs($user)->get(route($page))->assertForbidden();
        }
    }
}
