<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\NotificationSender;
use App\Support\SectionCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            'section' => ['nullable', 'string', Rule::in(SectionCatalog::labels())],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female'])],
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
            'gender' => $validated['role'] === 'student' && filled($validated['gender'] ?? null) ? $validated['gender'] : null,
            'approval_status' => $validated['approval_status'],
            'password' => $validated['password'],
            ...$approvalChanges,
        ]);

        if ($user->isStudent() && $user->isApproved()) {
            NotificationSender::notifyStudentEnrolled($user, $request->user());
        }

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
            'sections' => SectionCatalog::labels(),
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
            'section' => ['nullable', 'string', Rule::in(SectionCatalog::labels())],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female'])],
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
            'gender' => $validated['role'] === 'student' && filled($validated['gender'] ?? null) ? $validated['gender'] : null,
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

    public function bulkDestroy(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1', 'max:100'],
            'user_ids.*' => ['required', 'integer', 'distinct'],
        ], [
            'user_ids.required' => 'Select at least one account to delete.',
            'user_ids.min' => 'Select at least one account to delete.',
        ]);

        $selectedIds = collect($validated['user_ids'])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $selectedUsers = User::query()->whereKey($selectedIds)->get();
        $deletableUsers = $selectedUsers->filter(fn (User $user): bool =>
            in_array($user->role, ['student', 'teacher'], true)
            && ! $user->isSystemAdministrator()
            && ! $request->user()->is($user)
        );

        DB::transaction(function () use ($deletableUsers): void {
            $deletableUsers->each(fn (User $user) => $user->delete());
        });

        $deletedCount = $deletableUsers->count();
        $skippedCount = $selectedIds->count() - $deletedCount;
        $message = $deletedCount === 1
            ? '1 account has been moved to Trash.'
            : "{$deletedCount} accounts have been moved to Trash.";

        if ($skippedCount > 0) {
            $message .= ' '.($skippedCount === 1
                ? '1 protected or unavailable account was skipped.'
                : "{$skippedCount} protected or unavailable accounts were skipped.");
        }

        return redirect()
            ->to(route('admin.dashboard').'#user-management')
            ->with('status', $message);
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

        try {
            $this->permanentlyDelete($user);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('admin.users.trash')
                ->with('status', "{$user->name} could not be permanently deleted. The account remains in Trash.");
        }

        return redirect()->route('admin.users.trash')->with('status', "{$user->name} has been permanently deleted.");
    }

    public function emptyTrash(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $trashedUsers = User::onlyTrashed()->get();

        if ($trashedUsers->isEmpty()) {
            return redirect()->route('admin.users.trash')->with('status', 'Trash is already empty.');
        }

        $deletedCount = 0;
        $failedCount = 0;

        foreach ($trashedUsers as $user) {
            if ($request->user()->is($user) || $user->isSystemAdministrator()) {
                continue;
            }

            try {
                $this->permanentlyDelete($user);
                $deletedCount++;
            } catch (\Throwable $exception) {
                report($exception);
                $failedCount++;
            }
        }

        if ($failedCount > 0) {
            $message = $deletedCount === 1
                ? '1 trashed user was permanently deleted.'
                : "{$deletedCount} trashed users were permanently deleted.";
            $message .= ' '.($failedCount === 1
                ? '1 account could not be deleted and remains in Trash.'
                : "{$failedCount} accounts could not be deleted and remain in Trash.");

            return redirect()->route('admin.users.trash')->with('status', $message);
        }

        return redirect()->route('admin.users.trash')->with('status', 'All trashed users have been permanently deleted.');
    }

    private function permanentlyDelete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            if (Schema::hasTable('personal_access_tokens')) {
                $user->tokens()->delete();
            }

            $pushSubscriptionsTable = (string) config('webpush.table_name', 'push_subscriptions');
            if (Schema::hasTable($pushSubscriptionsTable)) {
                $user->pushSubscriptions()->delete();
            }

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            $user->forceDelete();
        });
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
