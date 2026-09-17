<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AvatarWardrobe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class WardrobeCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_collection_has_ten_distinct_new_coin_rewards_and_matching_art(): void
    {
        $counts = ['nova' => 0, 'lyra' => 0];
        foreach (AvatarWardrobe::options() as $key => $option) {
            foreach ($option['items'] as $value => $item) {
                if (!isset($item['collection'])) {
                    continue;
                }
                $counts[$item['collection']]++;
                $this->assertGreaterThanOrEqual(25, $item['cost']);
                $look = array_replace(AvatarWardrobe::defaults(), ['character' => $item['collection'], $key => $value]);
                $html = Blade::render('<x-wardrobe-character :appearance="$look" />', ['look' => $look]);
                $this->assertStringContainsString('data-avatar-part="'.$key.'" data-avatar-value="'.$value.'" style="display: inline"', $html);
                $this->assertSame($look, AvatarWardrobe::resolve($look));
            }
        }
        $this->assertSame(['nova' => 10, 'lyra' => 10], $counts);
    }

    public function test_everyday_skirt_is_free_and_can_be_saved_and_shown_on_profile(): void
    {
        $student = User::factory()->create(['gender' => 'female']);
        $look = array_replace(AvatarWardrobe::defaults('female'), ['bottoms' => 'skirt']);
        $this->actingAs($student)->patch(route('student.wardrobe.update'), ['avatar' => $look])->assertSessionHasNoErrors();
        $this->assertSame('skirt', $student->fresh()->avatar_config['bottoms']);
        $this->assertSame(0, $student->practiceCoinBalance());
        $this->get(route('student.wardrobe.edit'))->assertOk()->assertSee('Everyday skirt')->assertSee('Outfit collections');
        $this->get(route('profile.edit'))->assertOk()
            ->assertSee('data-avatar-part="bottoms" data-avatar-value="skirt" style="display: inline"', false);
    }

    public function test_all_new_rewards_require_purchase_then_persist_for_either_character(): void
    {
        $student = User::factory()->create();
        $student->practiceCoinTransactions()->create(['amount' => 2000, 'description' => 'Test rewards']);
        $this->actingAs($student);
        $spent = 0;
        foreach (AvatarWardrobe::options() as $key => $option) {
            foreach ($option['items'] as $value => $item) {
                if (!isset($item['collection'])) {
                    continue;
                }
                $look = array_replace(AvatarWardrobe::defaults(), ['character' => $item['collection'], $key => $value]);
                $this->patch(route('student.wardrobe.update'), ['avatar' => $look])->assertSessionHasErrors('avatar.'.$key);
                $this->post(route('student.wardrobe.purchase'), ['item' => $key.':'.$value, 'cost' => 0])
                    ->assertSessionHasNoErrors()->assertRedirect(route('student.wardrobe.edit', ['tab' => $option['tab'], 'collection' => $item['collection']]));
                $this->post(route('student.wardrobe.purchase'), ['item' => $key.':'.$value])->assertSessionHasNoErrors();
                $spent += $item['cost'];
                $this->assertSame(2000 - $spent, $student->practiceCoinBalance());
                $this->patch(route('student.wardrobe.update'), ['avatar' => $look])->assertSessionHasNoErrors();
                $this->assertEquals($look, $student->fresh()->avatar_config);
                $look['character'] = $item['collection'] === 'nova' ? 'lyra' : 'nova';
                $this->patch(route('student.wardrobe.update'), ['avatar' => $look])->assertSessionHasNoErrors();
                $this->assertEquals($look, $student->fresh()->avatar_config);
            }
        }
        $this->assertCount(20, $student->unlockedAvatarItems());
        $this->assertDatabaseCount('practice_coin_transactions', 21);
    }
}
