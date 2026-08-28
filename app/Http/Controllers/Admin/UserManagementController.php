<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100', 'not_regex:/\d/'],
            'middle_name' => ['nullable', 'string', 'max:100', 'not_regex:/\d/'],
            'last_name' => ['required', 'string', 'max:100', 'not_regex:/\d/'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', 'string', Rule::in(['admin', 'teacher', 'student'])],
            'section' => ['nullable', 'string', Rule::in(['Section A', 'Section B', 'Section C'])],
            'approval_status' => ['required', 'string', Rule::in(['approved', 'pending', 'rejected'])],
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

        $approvalChanges = match ($validated['approval_status']) {
            'approved' => [
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
            ],
            default => [
                'approved_at' => null,
                'approved_by' => $request->user()->id,
            ],
        };

        $user = User::create([
            'name' => $fullName,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'section' => filled($validated['section'] ?? null) ? $validated['section'] : null,
            'approval_status' => $validated['approval_status'],
            'password' => $validated['password'],
            ...$approvalChanges,
        ]);

        return redirect()
            ->to(route('admin.dashboard').'#user-management')
            ->with('status', "{$user->name}'s account has been created.");
    }

    public function edit(Request $request, User $user): View|RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if ($user->isSystemAdministrator()) {
            return redirect()
                ->to(route('admin.dashboard').'#user-management')
                ->with('status', 'The system administrator account is fixed and cannot be edited.');
        }

        return view('admin.users.edit', [
            'adminUser' => $request->user(),
            'adminInitials' => $this->initials($request->user()->name),
            'managedUser' => $user,
            'managedUserInitials' => $this->initials($user->name),
            'sections' => ['Section A', 'Section B', 'Section C'],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if ($user->isSystemAdministrator()) {
            return redirect()
                ->to(route('admin.dashboard').'#user-management')
                ->with('status', 'The system administrator account is fixed and cannot be edited.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in(['admin', 'teacher', 'student'])],
            'section' => ['nullable', 'string', Rule::in(['Section A', 'Section B', 'Section C'])],
            'approval_status' => ['required', 'string', Rule::in(['approved', 'pending', 'rejected'])],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($request->user()->is($user) && ($validated['role'] !== 'admin' || $validated['approval_status'] !== 'approved')) {
            throw ValidationException::withMessages([
                'role' => 'You cannot remove your own admin access or approval status.',
            ]);
        }

        $approvalChanges = match ($validated['approval_status']) {
            'approved' => [
                'approved_at' => $user->approved_at ?? now(),
                'approved_by' => $user->approved_by ?? $request->user()->id,
            ],
            default => [
                'approved_at' => null,
                'approved_by' => $request->user()->id,
            ],
        };

        $changes = [
            'name' => $validated['name'],
            'role' => $validated['role'],
            'section' => filled($validated['section'] ?? null) ? $validated['section'] : null,
            'approval_status' => $validated['approval_status'],
            ...$approvalChanges,
        ];

        if (filled($validated['password'] ?? null)) {
            $changes['password'] = $validated['password'];
        }

        $user->forceFill($changes)->save();

        return redirect()
            ->to(route('admin.dashboard').'#user-management')
            ->with('status', "{$user->name}'s profile has been updated.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if ($user->isSystemAdministrator()) {
            return redirect()
                ->to(route('admin.dashboard').'#user-management')
                ->with('status', 'The system administrator account cannot be deleted.');
        }

        if ($request->user()->is($user)) {
            return redirect()
                ->to(route('admin.dashboard').'#user-management')
                ->with('status', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->to(route('admin.dashboard').'#user-management')
            ->with('status', "{$user->name} has been deleted.");
    }

    public function trash(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'role' => (string) $request->query('role', ''),
        ];

        $query = User::onlyTrashed()->orderBy('deleted_at', 'desc')
            ->when($filters['search'] !== '', function ($q) use ($filters) {
                $q->where(function ($q) use ($filters) {
                    $q->where('name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('email', 'like', '%'.$filters['search'].'%');
                });
            })
            ->when(in_array($filters['role'], ['admin', 'teacher', 'student'], true), function ($q) use ($filters) {
                $q->where('role', $filters['role']);
            });

        $trashed = $query->paginate(10)->withQueryString();

        return view('admin.users.trash', [
            'adminUser' => $request->user(),
            'adminInitials' => $this->initials($request->user()->name),
            'trashedUsers' => $trashed,
            'filters' => $filters,
        ]);
    }

    public function restore(Request $request, $id): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $user = User::withTrashed()->findOrFail($id);

        if ($user->trashed()) {
            $user->restore();
            return redirect()->route('admin.users.trash')->with('status', "{$user->name} has been restored.");
        }

        return redirect()->route('admin.users.trash')->with('status', 'User is not in trash.');
    }

    public function forceDelete(Request $request, $id): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $user = User::withTrashed()->findOrFail($id);

        if ($user->isSystemAdministrator()) {
            return redirect()->route('admin.users.trash')->with('status', 'The system administrator account cannot be permanently deleted.');
        }

        if ($request->user()->is($user)) {
            return redirect()->route('admin.users.trash')->with('status', 'You cannot delete your own account.');
        }

        $user->forceDelete();

        return redirect()->route('admin.users.trash')->with('status', "{$user->name} has been permanently deleted.");
    }

    public function emptyTrash(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $trashedUsers = User::onlyTrashed()->get();

        if ($trashedUsers->isEmpty()) {
            return redirect()->route('admin.users.trash')->with('status', 'Trash is already empty.');
        }

        $trashedUsers->each(function (User $user) use ($request) {
            if (! $request->user()->is($user) && ! $user->isSystemAdministrator()) {
                $user->forceDelete();
            }
        });

        return redirect()->route('admin.users.trash')->with('status', 'All trashed users have been permanently deleted.');
    }

    private function initials(string $name): string
    {
        return Str::of($name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $segment) => Str::upper(Str::substr($segment, 0, 1)))
            ->implode('');
    }
}
