<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:100', 'not_regex:/\d/'],
            'middle_name' => ['nullable', 'string', 'max:100', 'not_regex:/\d/'],
            'last_name' => ['required', 'string', 'max:100', 'not_regex:/\d/'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', 'string', 'in:student,teacher'],
            'section' => ['required_if:role,teacher', 'nullable', 'string', Rule::in(['Section A', 'Section B', 'Section C'])],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'first_name.not_regex' => 'The first name must not contain numbers.',
            'middle_name.not_regex' => 'The middle name must not contain numbers.',
            'last_name.not_regex' => 'The last name must not contain numbers.',
            'section.required_if' => 'Please choose the section you are assigned to.',
        ]);

        $selectedRole = $request->role;
        $requiresApproval = in_array($selectedRole, ['student', 'teacher'], true);
        $roleLabel = ucfirst($selectedRole);
        $fullName = collect([
            $request->first_name,
            $request->middle_name,
            $request->last_name,
        ])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->implode(' ');

        $user = User::create([
            'name' => $fullName,
            'email' => $request->email,
            'role' => $selectedRole,
            'section' => $selectedRole === 'teacher' ? $request->section : null,
            'teacher_token' => null,
            'approval_status' => $requiresApproval ? 'pending' : 'approved',
            'approved_at' => $requiresApproval ? null : now(),
            'password' => $request->password,
        ]);

        event(new Registered($user));

        $statusMessage = $requiresApproval
            ? "{$roleLabel} account request submitted. Please wait for an administrator to approve your access."
            : 'Account created successfully. Please log in to continue.';

        return redirect()->route('login')->with('status', $statusMessage);
    }
}
