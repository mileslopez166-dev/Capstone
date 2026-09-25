<?php

namespace Tests\Feature;

use App\Models\{Assessment, PracticeMission, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeacherProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function upload(User $teacher, ?UploadedFile $file = null)
    {
        return $this->actingAs($teacher)->post(route('teacher.photo.store'), [
            'photo' => $file ?? UploadedFile::fake()->image('portrait.png', 800, 600),
        ]);
    }

    public function test_only_teachers_have_photo_controls_and_children_keep_avatars(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher)->get(route('profile.edit'))->assertOk()
            ->assertSee('data-teacher-photo-form', false)->assertSee('Save Photo')->assertDontSee('Remove Photo');
        $this->actingAs(User::factory()->create())->get(route('profile.edit'))->assertOk()
            ->assertDontSee('data-teacher-photo-form', false)->assertDontSee('name="photo"', false)->assertSee('Customize Avatar');
        $this->actingAs(User::factory()->admin()->create())->get(route('profile.edit'))->assertOk()
            ->assertDontSee('data-teacher-photo-form', false);
    }

    public function test_upload_is_reencoded_and_stored_privately_for_the_current_teacher(): void
    {
        $teacher = User::factory()->teacher()->create();
        $other = User::factory()->teacher()->create();
        $this->actingAs($teacher)->post(route('teacher.photo.store'), [
            'photo' => UploadedFile::fake()->image('personal-camera-photo.png', 1000, 800),
            'user_id' => $other->id,
        ])->assertRedirect(route('profile.edit'))->assertSessionHas('status', 'teacher-photo-updated');
        $path = $teacher->fresh()->teacher_photo_path;
        $this->assertStringStartsWith('teacher-photos/'.$teacher->id.'/', $path);
        $this->assertStringEndsWith('.jpg', $path);
        $this->assertStringNotContainsString('personal-camera-photo', $path);
        $image = getimagesizefromstring(Storage::disk('local')->get($path));
        $this->assertSame([640, 512, 'image/jpeg'], [$image[0], $image[1], $image['mime']]);
        $this->assertNull($other->fresh()->teacher_photo_path);
        $this->assertArrayNotHasKey('teacher_photo_path', $teacher->fresh()->toArray());
        $this->assertFalse($teacher->isFillable('teacher_photo_path'));
    }

    public function test_replacing_and_removing_photo_cleans_up_previous_file(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->upload($teacher)->assertSessionHasNoErrors();
        $old = $teacher->fresh()->teacher_photo_path;
        $this->upload($teacher, UploadedFile::fake()->image('new.jpg', 120, 120))->assertSessionHasNoErrors();
        $new = $teacher->fresh()->teacher_photo_path;
        Storage::disk('local')->assertMissing($old);
        Storage::disk('local')->assertExists($new);
        $this->actingAs($teacher->fresh())->get(route('profile.edit'))->assertSee('Remove Photo');
        $this->delete(route('teacher.photo.destroy'))->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'teacher-photo-removed');
        Storage::disk('local')->assertMissing($new);
        $this->assertNull($teacher->fresh()->teacher_photo_path);
        $this->get(route('teacher.photo.show', $teacher))->assertNotFound();
        $this->actingAs($teacher->fresh())->get(route('profile.edit'))->assertDontSee('Remove Photo')->assertSee($teacher->name.' initials');
        $this->delete(route('teacher.photo.destroy'))->assertRedirect(route('profile.edit'));
    }

    public function test_students_admins_and_unapproved_teachers_cannot_upload_or_remove_photos(): void
    {
        foreach ([User::factory()->create(), User::factory()->admin()->create(), User::factory()->teacher()->pendingApproval()->create()] as $user) {
            $this->upload($user)->assertForbidden();
            $this->actingAs($user)->delete(route('teacher.photo.destroy'))->assertForbidden();
            $this->assertNull($user->fresh()->teacher_photo_path);
        }
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_student_cannot_set_photo_or_change_role_through_profile_update(): void
    {
        $student = User::factory()->create();
        $this->actingAs($student)->patch(route('profile.update'), [
            'name' => $student->name, 'email' => $student->email, 'role' => 'teacher',
            'teacher_photo_path' => 'teacher-photos/forged.jpg',
        ])->assertSessionHasNoErrors();
        $this->assertSame('student', $student->fresh()->role);
        $this->assertNull($student->fresh()->teacher_photo_path);
    }

    public function test_photo_requires_signed_in_approved_viewer_and_approved_teacher(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->upload($teacher);
        $url = route('teacher.photo.show', $teacher);
        $student = User::factory()->create();
        $response = $this->actingAs($student)->get($url)->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame(Storage::disk('local')->get($teacher->fresh()->teacher_photo_path), $response->streamedContent());
        $this->actingAs(User::factory()->pendingApproval()->create())->get($url)->assertForbidden();
        $this->actingAs($student);
        $teacher->update(['approval_status' => 'pending']);
        $this->get($url)->assertNotFound();
        $teacher->update(['approval_status' => 'approved']);
        $teacher->delete();
        $this->get($url)->assertNotFound();
    }

    public function test_guest_cannot_view_upload_or_remove_teacher_photos(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->get(route('teacher.photo.show', $teacher))->assertRedirect(route('login'));
        $this->post(route('teacher.photo.store'))->assertRedirect(route('login'));
        $this->delete(route('teacher.photo.destroy'))->assertRedirect(route('login'));
    }

    public function test_photo_endpoint_never_serves_student_or_admin_images_and_handles_missing_files(): void
    {
        $student = User::factory()->create();
        Storage::disk('local')->put('test.jpg', 'private file');
        foreach ([$student, User::factory()->admin()->create()] as $target) {
            $target->forceFill(['teacher_photo_path' => 'test.jpg'])->save();
            $this->actingAs($student)->get(route('teacher.photo.show', $target))->assertNotFound();
        }
        $teacher = User::factory()->teacher()->create(['teacher_photo_path' => 'missing.jpg']);
        $this->get(route('teacher.photo.show', $teacher))->assertNotFound();
    }

    public function test_invalid_files_leave_saved_photo_unchanged(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->upload($teacher);
        $path = $teacher->fresh()->teacher_photo_path;
        $invalidFiles = [
            UploadedFile::fake()->createWithContent('script.jpg', '<?php echo "not an image";'),
            UploadedFile::fake()->createWithContent('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"></svg>'),
            UploadedFile::fake()->image('small.png', 32, 32),
            UploadedFile::fake()->image('wide.png', 4097, 64),
            UploadedFile::fake()->image('large.png', 100, 100)->size(2049),
        ];
        foreach ($invalidFiles as $file) {
            $this->upload($teacher, $file)->assertSessionHasErrorsIn('teacherPhoto', 'photo');
            $this->assertSame($path, $teacher->fresh()->teacher_photo_path);
        }
        $this->assertSame([$path], Storage::disk('local')->allFiles());
    }

    public function test_camera_orientation_is_applied_and_metadata_is_removed(): void
    {
        $teacher = User::factory()->teacher()->create();
        $jpeg = UploadedFile::fake()->image('phone.jpg', 120, 80)->get();
        $exif = "Exif\0\0II*\0\x08\0\0\0\x01\0\x12\x01\x03\0\x01\0\0\0\x06\0\0\0\0\0\0\0";
        $comment = 'private camera location';
        $jpeg = substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif
            ."\xFF\xFE".pack('n', strlen($comment) + 2).$comment.substr($jpeg, 2);
        $this->upload($teacher, UploadedFile::fake()->createWithContent('phone.jpg', $jpeg))->assertSessionHasNoErrors();
        $saved = Storage::disk('local')->get($teacher->fresh()->teacher_photo_path);
        $dimensions = getimagesizefromstring($saved);
        $this->assertSame([80, 120], [$dimensions[0], $dimensions[1]]);
        $this->assertStringNotContainsString('Exif', $saved);
        $this->assertStringNotContainsString($comment, $saved);
    }

    public function test_webp_is_supported_and_storage_failure_preserves_existing_photo(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->upload($teacher, UploadedFile::fake()->image('portrait.webp', 100, 100))->assertSessionHasNoErrors();
        $path = $teacher->fresh()->teacher_photo_path;
        $disk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $disk->shouldReceive('put')->once()->andReturn(false);
        $disk->shouldReceive('delete')->once()->with(\Mockery::on(fn ($deleted) => $deleted !== $path))->andReturn(true);
        $this->upload($teacher)->assertSessionHasErrorsIn('teacherPhoto', 'photo');
        $this->assertSame($path, $teacher->fresh()->teacher_photo_path);
    }

    public function test_students_see_the_assigned_teachers_photo_on_assessments_and_practice(): void
    {
        $teacher = User::factory()->teacher()->create(['name' => 'Morgan Reyes']);
        $this->upload($teacher);
        $other = User::factory()->teacher()->create(['teacher_photo_path' => 'other.jpg']);
        $assessment = Assessment::create([
            'created_by' => $teacher->id, 'title' => 'Our Reading Story', 'subject' => 'literacy',
            'quiz_type' => 'multiple_choice', 'delivery_method' => 'manual', 'target_section' => 'all',
            'assessment_type' => 'oral_reading', 'story_title' => 'Story', 'story_description' => 'Read this story.',
            'status' => 'published', 'manual_questions' => [],
        ]);
        $student = User::factory()->create();
        $this->actingAs($student);
        foreach ([route('student.dashboard'), route('student.activities', ['subject' => 'literacy']), route('student.assessments.show', $assessment)] as $url) {
            $this->get($url)->assertOk()->assertSee('Morgan Reyes')->assertSee(route('teacher.photo.show', $teacher), false)
                ->assertDontSee(route('teacher.photo.show', $other), false);
        }
        $mission = PracticeMission::create([
            'student_id' => $student->id, 'teacher_id' => $teacher->id, 'title' => 'Reading Practice',
            'source_title' => 'Reading', 'questions' => [], 'words' => [], 'status' => 'assigned', 'reward_coins' => 25,
        ]);
        foreach ([route('student.practice.index'), route('student.practice.show', $mission)] as $url) {
            $this->get($url)->assertOk()->assertSee(route('teacher.photo.show', $teacher), false);
        }
    }
}
