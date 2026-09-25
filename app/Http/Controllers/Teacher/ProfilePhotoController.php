<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TeacherProfilePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfilePhotoController extends Controller
{
    private function authorizeTeacher(User $user): void
    {
        abort_unless($user->isTeacher() && $user->isApproved(), 403);
    }

    public function store(Request $request)
    {
        $this->authorizeTeacher($request->user());
        $request->validateWithBag('teacherPhoto', [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=64,max_width=4096,max_height=4096'],
        ]);
        $image = TeacherProfilePhoto::encode($request->file('photo'));
        $path = 'teacher-photos/'.$request->user()->id.'/'.Str::uuid().'.jpg';
        $disk = Storage::disk('local');
        try {
            $previous = DB::transaction(function () use ($request, $image, $path, $disk) {
                $teacher = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $this->authorizeTeacher($teacher);
                if (! $disk->put($path, $image)) {
                    throw ValidationException::withMessages(['photo' => 'The photo could not be saved. Please try again.'])->errorBag('teacherPhoto');
                }
                $previous = $teacher->teacher_photo_path;
                $teacher->forceFill(['teacher_photo_path' => $path])->save();
                return $previous;
            });
        } catch (\Throwable $error) {
            $disk->delete($path);
            throw $error;
        }
        if ($previous) $disk->delete($previous);

        return redirect()->route('profile.edit')->with('status', 'teacher-photo-updated');
    }

    public function destroy(Request $request)
    {
        $this->authorizeTeacher($request->user());
        $previous = DB::transaction(function () use ($request) {
            $teacher = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $this->authorizeTeacher($teacher);
            $previous = $teacher->teacher_photo_path;
            $teacher->forceFill(['teacher_photo_path' => null])->save();
            return $previous;
        });
        if ($previous) Storage::disk('local')->delete($previous);

        return redirect()->route('profile.edit')->with('status', 'teacher-photo-removed');
    }

    public function show(Request $request, User $teacher)
    {
        abort_unless($request->user()->isApproved(), 403);
        abort_unless($teacher->isTeacher() && $teacher->isApproved() && $teacher->teacher_photo_path, 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($teacher->teacher_photo_path), 404);

        return $disk->response($teacher->teacher_photo_path, 'teacher-profile.jpg', [
            'Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }
}
