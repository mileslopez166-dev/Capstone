<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\NotificationSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class StudentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $teacher = $request->user();

        abort_unless($teacher?->isTeacher(), 403);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100', 'not_regex:/\d/'],
            'middle_name' => ['nullable', 'string', 'max:100', 'not_regex:/\d/'],
            'last_name' => ['required', 'string', 'max:100', 'not_regex:/\d/'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'section' => ['nullable', 'string', Rule::in(['Section A', 'Section B', 'Section C'])],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'first_name.not_regex' => 'The first name must not contain numbers.',
            'middle_name.not_regex' => 'The middle name must not contain numbers.',
            'last_name.not_regex' => 'The last name must not contain numbers.',
        ]);

        $fullName = collect([
            $validated['first_name'],
            $validated['middle_name'] ?? null,
            $validated['last_name'],
        ])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->implode(' ');

        $section = filled($validated['section'] ?? null)
            ? $validated['section']
            : $teacher->section;

        $student = User::create([
            'name' => $fullName,
            'email' => $validated['email'],
            'role' => 'student',
            'section' => $section,
            'gender' => $validated['gender'],
            'teacher_token' => null,
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $teacher->id,
            'password' => $validated['password'],
        ]);

        NotificationSender::notifyStudentEnrolled($student, $teacher);

        return redirect()
            ->route('students.index')
            ->with('status', "{$student->name}'s student account has been created.");
    }
}
