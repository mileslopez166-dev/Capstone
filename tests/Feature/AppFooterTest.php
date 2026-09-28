<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppFooterTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_pages_share_a_compact_footer_with_support_only(): void
    {
        $this->assertRoleFooter(User::factory()->teacher()->create(), [
            'teacher.dashboard', 'students.index', 'students.create', 'reports.index', 'assessments.index',
            'assessments.create', 'worksheets.index', 'worksheets.reviews',
            'teacher.practice.index', 'profile.edit', 'support.developing',
        ]);
    }

    public function test_student_pages_share_a_compact_footer_with_support_only(): void
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
            $this->assertSame(1, $links->length);
            $this->assertSame(route('support.developing'), $links->item(0)->getAttribute('href'));
            $this->assertStringNotContainsString('Dashboard', $footer->textContent);
            $this->assertSame(1, substr_count($footer->textContent, 'AI-PGAALS'));
            $this->assertSame(1, $xpath->query('preceding::main', $footer)->length);
            if (getenv('CAPTURE_FOOTER_FIXTURES') && in_array($page, ['student.activities', 'students.index'])) {
                file_put_contents(storage_path('app/footer-'.$user->role.'.html'), $response->getContent());
            }
        }
    }
}
