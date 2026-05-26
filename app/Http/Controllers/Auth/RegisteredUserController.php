<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', 'string', 'in:student,teacher'],
            'section' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $selectedRole = $request->role;
        $requiresApproval = in_array($selectedRole, ['student', 'teacher'], true);
        $roleLabel = ucfirst($selectedRole);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $selectedRole,
            'section' => $request->section,
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
