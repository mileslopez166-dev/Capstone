<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\AdminAccountBootstrapper;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'role' => ['required', 'string', 'in:admin,student,teacher'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();
        app(AdminAccountBootstrapper::class)->ensureExists();

        $email = $this->string('email')->toString();
        $password = $this->string('password')->toString();
        $remember = $this->boolean('remember');

        // First, check if the user is an admin trying to log in.
        $user = User::where('email', $email)->first();

        // DEBUG: If you are still having issues, uncomment the following line to inspect the user data.
        // if ($user && $email === 'admin@aipgaals.com') {
        //     dd($user->toArray(), $user->isAdmin(), Hash::check($password, $user->password));
        // }

        if ($user && $user->isAdmin() && Hash::check($password, $user->password)) {
            Auth::login($user, $remember);
            RateLimiter::clear($this->throttleKey());
            return;
        }

        if ($user && ! $user->isAdmin() && ! $user->isApproved() && Hash::check($password, $user->password)) {
            $roleLabel = $user->isTeacher() ? 'teacher' : 'student';
            $message = $user->approval_status === 'rejected'
                ? "Your {$roleLabel} account request was declined. Please contact the administrator."
                : "Your {$roleLabel} account is still pending administrator approval.";

            throw ValidationException::withMessages([
                'email' => $message,
            ]);
        }

        $selectedRole = $this->string('role')->toString();

        // Repair legacy accounts that predate role enforcement by adopting the selected role on login.
        if ($user && blank($user->role) && Hash::check($password, $user->password)) {
            $user->forceFill(['role' => $selectedRole])->save();
            Auth::login($user, $remember);
            RateLimiter::clear($this->throttleKey());
            return;
        }

        // If not an admin, proceed with role-based authentication.
        $credentials = [
            'email' => $email,
            'password' => $password,
            'role' => $selectedRole,
        ];

        if (! Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match the selected account type.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
