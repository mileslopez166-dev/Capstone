<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class TeacherAiAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        RateLimiter::clear('teacher-ai:1:minute');
        RateLimiter::clear('teacher-ai:1:day');
        config(['tutor.enabled' => true, 'tutor.key' => 'unit-test-placeholder', 'tutor.model' => 'gpt-6-astra']);
        Http::preventStrayRequests();
        $this->fakeReply();
    }

    public function test_teacher_assistant_page_is_for_approved_teachers_only(): void
    {
        $this->get(route('teacher.ai-assistant.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('teacher.ai-assistant.index'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('teacher.ai-assistant.index'))->assertForbidden();
        $this->actingAs(User::factory()->teacher()->pendingApproval()->create())->get(route('teacher.ai-assistant.index'))->assertForbidden();

        $this->actingAs(User::factory()->teacher()->create())
            ->get(route('teacher.ai-assistant.index'))
            ->assertOk()
            ->assertSeeText('Teachers AI Assistant')
            ->assertDontSeeText('Ask Tutor');

        Http::assertNothingSent();
    }

    public function test_teacher_assistant_send_uses_responses_without_storage(): void
    {
        $teacher = User::factory()->teacher()->create();
        $payload = ['question' => 'Create a Grade 6 reading intervention plan.', 'request_id' => (string) Str::uuid()];

        $this->actingAs($teacher)->postJson(route('teacher.ai-assistant.send'), $payload)
            ->assertOk()
            ->assertJsonPath('turn.answer', 'Try a short rereading activity, then ask two evidence questions.');

        Http::assertSentCount(3);
        Http::assertSent(function ($request) use ($payload) {
            if (! str_ends_with($request->url(), '/responses')) return false;
            $this->assertFalse($request['store']);
            $this->assertSame('gpt-6-astra', $request['model']);
            $this->assertStringContainsString('Teachers AI Assistant', $request['instructions']);
            $this->assertStringContainsString($payload['question'], $request->body());
            $this->assertFalse(isset($request['tools']));
            return $request->hasHeader('Authorization', 'Bearer unit-test-placeholder');
        });
    }

    public function test_unconfigured_teacher_assistant_makes_no_paid_calls(): void
    {
        $teacher = User::factory()->teacher()->create();
        config(['tutor.key' => '']);

        $this->actingAs($teacher)->get(route('teacher.ai-assistant.index'))
            ->assertOk()
            ->assertSeeText('not connected yet');

        $this->postJson(route('teacher.ai-assistant.send'), [
            'question' => 'Help me plan a lesson.',
            'request_id' => (string) Str::uuid(),
        ])->assertStatus(503);

        Http::assertNothingSent();
    }

    public function test_non_teachers_cannot_send_teacher_assistant_requests(): void
    {
        $payload = ['question' => 'Plan a lesson.', 'request_id' => (string) Str::uuid()];
        $this->postJson(route('teacher.ai-assistant.send'), $payload)->assertUnauthorized();

        foreach ([User::factory()->create(), User::factory()->admin()->create(), User::factory()->teacher()->pendingApproval()->create()] as $user) {
            $this->actingAs($user)->postJson(route('teacher.ai-assistant.send'), $payload)->assertForbidden();
        }

        Http::assertNothingSent();
    }

    private function fakeReply(array $overrides = []): void
    {
        Http::swap(new Factory());
        Http::preventStrayRequests();
        Http::fake(array_replace([
            'api.openai.com/v1/moderations' => Http::response(['results' => [['flagged' => false]]]),
            'api.openai.com/v1/responses' => Http::response(['status' => 'completed', 'output' => [
                ['type' => 'reasoning', 'summary' => []],
                ['type' => 'message', 'role' => 'assistant', 'content' => [['type' => 'output_text', 'text' => 'Try a short rereading activity, then ask two evidence questions.']]],
            ]]),
        ], $overrides));
    }
}
