<?php

namespace Tests\Feature;

use App\Support\{NumeracyWorksheets, WorksheetGames};
use Tests\TestCase;

class WorksheetGameFormatTest extends TestCase
{
    public function test_games_use_the_matching_real_worksheet_items_and_fields(): void
    {
        $one = NumeracyWorksheets::find(1);
        $archer = $one['pages'][0]['responses']['1-0']['game'];
        $this->assertSame('archer', $archer['type']);
        $this->assertSame(245, $archer['number']);
        $this->assertSame('divisors', $archer['field']);
        $this->assertSame(['2', '3', '5', '9', '10'], $archer['options']);
        $puzzle = $one['pages'][1]['responses']['1-0']['game'];
        $this->assertSame('puzzle', $puzzle['type']);
        $this->assertSame('2_4', $puzzle['pattern']);
        $this->assertSame(9, $puzzle['divisor']);
        $this->assertTrue($puzzle['smallest']);
        $rocket = NumeracyWorksheets::find(3)['pages'][1]['responses']['0-0']['game'];
        $this->assertSame('rocket', $rocket['type']);
        $this->assertSame(160, $rocket['number']);
        $this->assertSame(2, $rocket['divisor']);
        $this->assertTrue($rocket['reason']);
        $this->assertFalse(NumeracyWorksheets::find(4)['pages'][0]['responses']['0-0']['game']['reason']);
        $this->assertSame($archer, NumeracyWorksheets::pageConfig($one)[0]['responses']['1-0']['game']);
    }

    public function test_only_compatible_items_offer_games_and_reading_stays_reading(): void
    {
        foreach (NumeracyWorksheets::all() as $worksheet) {
            foreach ($worksheet['pages'] as $page) {
                foreach ($page['responses'] as $item) {
                    if ($worksheet['number'] <= 4) $this->assertNotNull($item['game']);
                    else $this->assertNull($item['game']);
                }
            }
        }
        $this->assertSame([], NumeracyWorksheets::find(3)['pages'][0]['responses']);
        $this->assertNull(WorksheetGames::forItem('A word problem', '', [['key' => 'answer', 'type' => 'number']]));
    }
}
