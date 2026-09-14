<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Trash | AI-PGAALS Admin Console</title>
    <x-admin-head />
</head>
<body class="staff-theme admin-theme min-h-screen overflow-x-hidden bg-surface text-on-surface">
    <div class="min-h-screen lg:flex">
        <x-admin-sidebar active="trash" :admin-user="$adminUser" :admin-initials="$adminInitials" />

        <div class="w-full lg:ml-64">
            <x-admin-topbar title="Trash" :admin-user="$adminUser" :admin-initials="$adminInitials" :search-action="route('admin.users.trash')" search-name="search" :search-value="$filters['search'] ?? ''" search-placeholder="Search deleted records..." />

            <main class="mx-auto max-w-7xl p-6 lg:p-8">
                @if (session('status'))
                    <div class="mb-6 rounded-DEFAULT border border-secondary/10 bg-secondary-container/35 px-5 py-4 text-sm font-medium text-on-surface">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="mb-6 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <h2 class="font-headline text-3xl font-bold text-on-surface">Trash</h2>
                        <p class="mt-2 max-w-2xl text-sm text-on-surface-variant">Review and restore or permanently delete trashed users.</p>
                    </div>
                    <div class="flex gap-3">
                        @if ($trashedUsers->total() > 0)
                            <form method="POST" action="{{ route('admin.users.trash.empty') }}" onsubmit="return confirm('Empty trash permanently? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg bg-error px-4 py-2 text-sm font-bold text-on-error hover:bg-error-dim" type="submit">Empty Trash</button>
                            </form>
                        @else
                            <button class="rounded-lg bg-surface-container-low px-4 py-2 text-sm font-bold text-on-surface-variant cursor-not-allowed" type="button" disabled>No items to empty</button>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="md:col-span-1 p-6 rounded-DEFAULT bg-surface-container-lowest border shadow-sm">
                        <p class="text-xs uppercase tracking-wider text-on-surface-variant">Total Deleted Users</p>
                        <h3 class="font-display text-4xl font-extrabold text-on-surface mt-2">{{ number_format($trashedUsers->total()) }}</h3>
                    </div>
                    <div class="md:col-span-1 p-6 rounded-DEFAULT bg-surface-container-lowest border shadow-sm">
                        <p class="text-xs uppercase tracking-wider text-on-surface-variant">Storage Cleaned</p>
                        <h3 class="font-display text-3xl font-extrabold text-on-surface mt-2">—</h3>
                    </div>
                    <div class="md:col-span-1 p-6 rounded-DEFAULT bg-primary text-on-primary shadow-xl">
                        <p class="text-xs uppercase tracking-wider opacity-80">Auto-Cleanup</p>
                        <h3 class="font-headline text-2xl font-bold mt-2">30 Days</h3>
                        <p class="mt-2 text-sm italic">Trash is automatically emptied after 30 days.</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 mb-6 bg-surface-container-low/50 p-4 rounded-full">
                    <div class="flex items-center gap-2 overflow-x-auto pb-1">
                        <a href="{{ route('admin.users.trash') }}" class="px-5 py-2 rounded-full bg-primary text-on-primary text-sm font-bold">All Roles</a>
                        <a href="{{ route('admin.users.trash', array_merge(request()->query(), ['role' => 'teacher'])) }}" class="px-5 py-2 rounded-full bg-surface-container-lowest text-sm">Teachers</a>
                        <a href="{{ route('admin.users.trash', array_merge(request()->query(), ['role' => 'student'])) }}" class="px-5 py-2 rounded-full bg-surface-container-lowest text-sm">Students</a>
                        <a href="{{ route('admin.users.trash', array_merge(request()->query(), ['role' => 'admin'])) }}" class="px-5 py-2 rounded-full bg-surface-container-lowest text-sm">Admins</a>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-on-surface-variant text-sm">Sorted by: <strong class="text-primary">Recently Deleted</strong></span>
                    </div>
                </div>

                <div class="bg-surface-container-lowest rounded-DEFAULT overflow-hidden border shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low text-on-surface-variant">
                                <th class="px-6 py-4 font-semibold">User</th>
                                <th class="px-6 py-4 font-semibold">Role</th>
                                <th class="px-6 py-4 font-semibold">Original Section</th>
                                <th class="px-6 py-4 font-semibold">Date Deleted</th>
                                <th class="px-6 py-4 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/15 text-sm">
                            @forelse ($trashedUsers as $user)
                                <tr class="transition-colors hover:bg-surface-bright">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-container/30 text-xs font-bold text-primary">{{ 
                                                \Illuminate\Support\Str::of($user->name)->explode(' ')->filter()->take(2)->map(fn($s)=>\Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($s,0,1)))->implode('') ?: 'U' }}</div>
                                            <div>
                                                <p class="font-bold text-on-surface">{{ $user->name }}</p>
                                                <p class="text-xs text-on-surface-variant">{{ $user->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide {{ $user->role === 'admin' ? 'bg-primary-container/30 text-primary' : ($user->role === 'teacher' ? 'bg-secondary-container text-secondary-dim' : 'bg-surface-container-high text-on-surface-variant') }}">{{ $user->role }}</span>
                                    </td>
                                    <td class="px-6 py-4">{{ $user->section ?: 'Unassigned' }}</td>
                                    <td class="px-6 py-4 text-on-surface-variant">{{ $user->deleted_at?->format('M d, Y h:i A') }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="inline-flex items-center gap-3">
                                            <form method="POST" action="{{ route('admin.users.restore', $user->id) }}">
                                                @csrf
                                                <button class="rounded-sm border border-outline-variant/30 bg-surface px-3 py-2 text-sm font-bold text-primary">Restore</button>
                                            </form>
                                            @if($user->isSystemAdministrator())
                                                <span class="inline-flex items-center gap-1 rounded-sm border border-outline-variant/30 px-3 py-2 text-sm font-bold text-on-surface-variant">
                                                    <span class="material-symbols-outlined text-[16px]">lock</span>
                                                    Fixed
                                                </span>
                                            @else
                                                <form method="POST" action="{{ route('admin.users.force-delete', $user->id) }}" onsubmit="return confirm('Permanently delete this user?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="rounded-sm border border-error px-3 py-2 text-sm font-bold text-error">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-6 py-10 text-center text-on-surface-variant" colspan="5">No trashed users.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="border-t border-outline-variant/15 p-4 flex items-center justify-between">
                        <div class="text-sm text-on-surface-variant">Showing {{ $trashedUsers->firstItem() ?? 0 }} to {{ $trashedUsers->lastItem() ?? 0 }} of {{ $trashedUsers->total() }} users</div>
                        <div>
                            {{ $trashedUsers->links() }}
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
