<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppFooterTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_pages_share_a_footer_with_teacher_dashboard_and_support_links(): void
    {
        $this->assertRoleFooter(User::factory()->teacher()->create(), [
            'teacher.dashboard', 'students.index', 'reports.index', 'assessments.index',
            'assessments.create', 'worksheets.index', 'worksheets.reviews',
            'teacher.practice.index', 'profile.edit', 'support.developing',
        ]);
    }

    public function test_student_pages_share_a_footer_with_student_dashboard_and_support_links(): void
    {
        $this->assertRoleFooter(User::factory()->create(), [
            'student.dashboard', 'student.activities', 'student.leaderboard',
            'student.rewards', 'worksheets.mission', 'student.practice.index',
            'student.wardrobe.edit', 'profile.edit', 'support.developing',
        ]);
    }

    private function assertRoleFooter(User $user, array $pages): void
    {
        $this->actingAs($user);
        foreach ($pages as $page) {
            $response = $this->get(route($page))->assertOk();
            $dom = new \DOMDocument();
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            $footers = $xpath->query('//body/footer[@aria-label="Site footer"]');
            $this->assertSame(1, $footers->length, $page.' has one page-level footer.');
            $footer = $footers->item(0);
            $this->assertStringContainsString((string) now()->year, $footer->textContent);
            $this->assertStringContainsString('AI-PGAALS', $footer->textContent);
            $links = $xpath->query('.//nav[@aria-label="Footer navigation"]/a', $footer);
            $this->assertSame(2, $links->length);
            $this->assertSame(route($user->dashboardRouteName()), $links->item(0)->getAttribute('href'));
            $this->assertSame(route('support.developing'), $links->item(1)->getAttribute('href'));
            $this->assertSame(1, $xpath->query('preceding::main', $footer)->length);
        }
    }
}
