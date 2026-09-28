<?php

namespace Tests\Feature;

use App\Models\{Assessment, AssessmentProgress, User, WorksheetAttempt};
use App\Support\{NumeracyWorksheets, WorksheetGames, WorksheetMission};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorksheetGameCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function assignment(User $teacher, ?int $number, array $extra = []): Assessment
    {
        return $teacher->createdAssessments()->create(array_merge([
            'title' => 'Math mission '.($number ?? 'legacy'), 'subject' => 'numeracy',
            'quiz_type' => $number ? 'worksheet' : 'multiple_choice', 'worksheet_number' => $number,
            'worksheet_total' => 10, 'status' => 'published', 'target_section' => 'section_a',
        ], $extra));
    }

    public function test_only_game_connected_worksheets_can_be_selected_and_created(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->assertSame([1, 2, 3, 4], NumeracyWorksheets::gameEnabledNumbers());
        $this->assertCount(35, NumeracyWorksheets::all());
        $this->actingAs($teacher)->get(route('worksheets.index'))->assertOk()
            ->assertViewHas('worksheets', fn ($worksheets) => array_column($worksheets, 'number') === [1, 2, 3, 4]);
        foreach ([1, 2, 3, 4] as $number) {
            $this->get(route('worksheets.create', $number))->assertOk();
            $this->post(route('worksheets.store', $number), ['title' => 'Game '.$number, 'target_section' => 'section_a',
                'worksheet_total' => 10, 'retry_limit' => '0', 'status' => 'draft'])->assertSessionHasNoErrors()->assertRedirect();
        }
        foreach ([5, 25, 35, 36] as $number) {
            $this->get(route('worksheets.create', $number))->assertNotFound();
            $this->post(route('worksheets.store', $number), ['title' => 'Unavailable'])->assertNotFound();
        }
        $this->assertDatabaseCount('assessments', 4);
    }

    public function test_activity_details_show_each_worksheets_actual_game_names_once(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['section' => 'Section A']);
        $expected = [1 => ['Divisibility Archer', 'Number Puzzle'], 2 => ['Divisibility Archer'],
            3 => ['Rocket Launch'], 4 => ['Rocket Launch', 'Divisibility Archer']];
        $assignments = [];
        foreach ($expected as $number => $names) {
            $assignments[$number] = $this->assignment($teacher, $number);
            $this->assertSame($names, WorksheetGames::namesForWorksheet(NumeracyWorksheets::find($number)));
        }
        $this->assertSame([], WorksheetGames::namesForWorksheet(NumeracyWorksheets::find(5)));
        $response = $this->actingAs($student)->get(route('student.activities', ['subject' => 'numeracy']))->assertOk();
        $dom = new \DOMDocument();
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(4, $xpath->query('//*[@data-assessment-games]'));
        foreach ($assignments as $number => $assessment) {
            $card = '//a[@href="'.route('student.assessments.show', $assessment).'"]';
            $names = $xpath->query($card.'//*[@data-assessment-game-names]');
            $this->assertCount(1, $names);
            $this->assertSame(implode(', ', $expected[$number]), trim($names->item(0)->textContent));
            $this->assertSame(count($expected[$number]) === 1 ? 'Game' : 'Games',
                trim($xpath->query($card.'//*[@data-assessment-games]/dt')->item(0)->textContent));
        }
        $this->get(route('student.activities', ['subject' => 'literacy']))->assertOk()->assertDontSee('data-assessment-games', false);
    }

    public function test_available_queues_search_and_mission_only_include_the_game_catalog(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['section' => 'Section A']);
        $one = $this->assignment($teacher, 1);
        $this->assignment($teacher, 2, ['status' => 'draft']);
        $this->assignment($teacher, 3, ['target_section' => 'section_b']);
        $four = $this->assignment($teacher, 4);
        $old = $this->assignment($teacher, 5);
        $this->assignment($teacher, null);
        AssessmentProgress::forAttempt($old, $student, 1)->update(['state' => ['page' => 1, 'step' => 'answer']]);
        $literacy = $this->assignment($teacher, null, ['subject' => 'literacy', 'title' => 'Reading story']);

        $this->actingAs($student)->get(route('student.activities', ['subject' => 'numeracy']))->assertOk()
            ->assertViewHas('pendingAssessments', fn ($items) => $items->pluck('id')->sort()->values()->all() === [$one->id, $four->id])
            ->assertViewHas('subjectCounts', fn ($counts) => $counts->get('numeracy') === 2 && $counts->get('literacy') === 1);
        $this->get(route('student.dashboard'))->assertOk()
            ->assertViewHas('pendingAssessments', fn ($items) => $items->pluck('id')->sort()->values()->all() === [$one->id, $four->id, $literacy->id]);
        $this->get(route('student.search', ['q' => 'Math']))->assertOk()
            ->assertViewHas('assessments', fn ($items) => $items->pluck('id')->sort()->values()->all() === [$one->id, $four->id]);
        $mission = WorksheetMission::forStudent($student);
        $this->assertSame([1, 2, 3, 4], $mission['steps']->pluck('number')->all());
        $this->assertSame(2, $mission['ready']);
        $this->assertSame(1, $mission['next']['number']);
        $this->assertSame('locked', $mission['steps']->firstWhere('number', 2)['state']);
        $this->assertSame('locked', $mission['steps']->firstWhere('number', 3)['state']);
        $this->assertDatabaseHas('assessment_progress', ['assessment_id' => $old->id, 'user_id' => $student->id]);
    }

    public function test_older_work_remains_reviewable_and_gradable_without_counting_toward_the_mission(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['section' => 'Section A']);
        $assessment = $this->assignment($teacher, 25);
        $progress = AssessmentProgress::forAttempt($assessment, $student, 1);
        $attempt = WorksheetAttempt::create(['assessment_id' => $assessment->id, 'user_id' => $student->id,
            'progress_id' => $progress->id, 'pages' => [['text' => 'Saved polygon answer']], 'total' => 10]);
        $this->actingAs($student)->get(route('worksheets.review', $attempt))->assertOk()->assertSee('Saved polygon answer');
        $this->assertSame(0, WorksheetMission::forStudent($student)['finished']);
        $this->actingAs($teacher)->get(route('assessments.show', $assessment))->assertOk();
        $this->get(route('worksheets.reviews'))->assertOk()->assertSee($assessment->title);
        $this->post(route('worksheets.grade', $attempt), ['score' => 8, 'feedback' => 'Reviewed existing work.'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(8, $attempt->fresh()->score);
        $this->assertDatabaseHas('assessment_submissions', ['assessment_id' => $assessment->id, 'points' => 2000]);
        $this->actingAs($student)->get(route('student.activities', ['subject' => 'numeracy']))->assertOk()
            ->assertViewHas('completedSubmissions', fn ($items) => $items->count() === 1 && $items->first()->assessment_id === $assessment->id);
        $this->get(route('worksheets.review', $attempt))->assertOk()->assertSee('Reviewed existing work.');
    }
}
