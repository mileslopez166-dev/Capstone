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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', 'string', 'in:student,teacher'],
            'teacher_registration_code' => [
                Rule::requiredIf($request->role === 'teacher'),
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    if ($request->role !== 'teacher') {
                        return;
                    }

                    $expectedCode = (string) config('auth.teacher_registration_code');

                    if ($expectedCode === '' || ! hash_equals($expectedCode, (string) $value)) {
                        $fail('The teacher registration code is invalid.');
                    }
                },
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => $request->password,
        ]);

        event(new Registered($user));

        return redirect()->route('login')->with('status', 'Account created successfully. Please log in to continue.');
    }
}
