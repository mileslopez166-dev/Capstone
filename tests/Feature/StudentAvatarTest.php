<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AvatarWardrobe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class StudentAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_open_wardrobe_and_existing_avatar_is_preserved_until_saved(): void
    {
        $student = User::factory()->create(['gender' => 'female']);

        $this->actingAs($student)->get(route('student.wardrobe.edit'))
            ->assertOk()
            ->assertSee('My Wardrobe')
            ->assertSee('Save Avatar')
            ->assertViewHas('appearance', fn (array $look) => $look['character'] === 'lyra');

        $this->get('/profile')->assertOk()->assertSee('student-girl.png')->assertSee('Customize Avatar');
        $this->assertNull($student->fresh()->avatar_config);
    }

    public function test_student_can_save_and_reload_an_outfit_without_changing_another_account(): void
    {
        $student = User::factory()->create(['gender' => 'male']);
        $other = User::factory()->create();
        $look = array_replace(AvatarWardrobe::defaults(), [
            'character' => 'lyra', 'outfit' => 'varsity', 'color' => 'coral', 'skin' => 'brown',
            'hair' => 'black', 'bottoms' => 'shorts', 'shoes' => 'high_tops',
            'headwear' => 'headphones', 'accessory' => 'glasses',
        ]);

        $this->actingAs($student)->patch(route('student.wardrobe.update'), [
            'avatar' => $look, 'user_id' => $other->id, 'role' => 'admin',
        ])->assertSessionHasNoErrors()->assertRedirect(route('student.wardrobe.edit'))
            ->assertSessionHas('status', 'avatar-saved');

        $student->refresh();
        $this->assertEquals($look, $student->avatar_config);
        $this->assertSame('student', $student->role);
        $this->assertSame('male', $student->gender);
        $this->assertNull($other->fresh()->avatar_config);

        $this->get(route('student.wardrobe.edit'))->assertOk()->assertViewHas('appearance', $look);
        foreach (['/profile', '/student/dashboard', '/student/activities'] as $path) {
            $this->get($path)->assertOk()->assertSee('data-character="lyra"', false)
                ->assertSee('data-outfit="varsity"', false)->assertSee('--avatar-color:#e9716a', false);
        }

        $this->patch('/profile', ['name' => $student->name, 'email' => $student->email])
            ->assertSessionHasNoErrors();
        $this->assertEquals($look, $student->fresh()->avatar_config);
    }

    public function test_invalid_and_incomplete_outfits_are_rejected_without_overwriting_saved_avatar(): void
    {
        $look = AvatarWardrobe::defaults();
        $student = User::factory()->create(['avatar_config' => $look]);
        $this->actingAs($student);

        $invalidLooks = [
            ['value' => array_replace($look, ['color' => 'url(https://invalid.test/image)']), 'error' => 'avatar.color'],
            ['value' => array_replace($look, ['headwear' => ['cap']]), 'error' => 'avatar.headwear'],
            ['value' => ['outfit' => 'hoodie'], 'error' => 'avatar.character'],
            ['value' => array_replace($look, ['unexpected' => true]), 'error' => 'avatar'],
            ['value' => 'invalid', 'error' => 'avatar'],
        ];

        foreach ($invalidLooks as $invalid) {
            $this->from(route('student.wardrobe.edit'))->patch(route('student.wardrobe.update'), ['avatar' => $invalid['value']])
                ->assertSessionHasErrors($invalid['error'])->assertRedirect(route('student.wardrobe.edit'));
            $this->get(route('student.wardrobe.edit'))->assertOk();
            $this->assertEquals($look, $student->fresh()->avatar_config);
        }
    }

    public function test_wardrobe_is_only_available_to_authenticated_students(): void
    {
        $this->get(route('student.wardrobe.edit'))->assertRedirect(route('login'));
        $this->patch(route('student.wardrobe.update'), ['avatar' => AvatarWardrobe::defaults()])
            ->assertRedirect(route('login'));

        foreach (['teacher', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get(route('student.wardrobe.edit'))->assertForbidden();
            $this->patch(route('student.wardrobe.update'), ['avatar' => AvatarWardrobe::defaults()])->assertForbidden();
            $this->assertNull($user->fresh()->avatar_config);
        }
    }

    public function test_shared_avatar_uses_its_students_outfit_instead_of_the_viewers(): void
    {
        $viewer = User::factory()->create(['avatar_config' => AvatarWardrobe::defaults()]);
        $student = User::factory()->create(['avatar_config' => array_replace(AvatarWardrobe::defaults(), [
            'character' => 'lyra', 'outfit' => 'explorer', 'headwear' => 'beanie',
        ])]);
        $this->actingAs($viewer);

        $html = Blade::render('<x-student-character :user="$student" variant="portrait" />', ['student' => $student]);
        $this->assertStringContainsString('data-character="lyra"', $html);
        $this->assertStringContainsString('data-outfit="explorer"', $html);
        $this->assertStringContainsString('data-headwear="beanie"', $html);
        $this->assertStringContainsString('viewBox="12 0 176 176"', $html);
    }
}
