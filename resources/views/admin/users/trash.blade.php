<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Trash | AI-PGAALS Admin Console</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary-fixed": "#44a5ff",
                        "surface-container-high": "#dfe3e7",
                        "primary-fixed-dim": "#2498f5",
                        "inverse-surface": "#0b0f11",
                        "surface-bright": "#f5f7fa",
                        "on-background": "#2c2f32",
                        "secondary-dim": "#005d16",
                        "outline": "#74777a",
                        "on-primary-container": "#002442",
                        "primary": "#005e9f",
                        "on-tertiary": "#fff4b0",
                        "on-secondary-fixed-variant": "#00691a",
                        "surface-container-highest": "#d9dde1",
                        "primary-container": "#44a5ff",
                        "on-surface-variant": "#595c5e",
                        "surface-container": "#e5e8ec",
                        "error": "#b31b25",
                        "error-dim": "#9f0519",
                        "surface-dim": "#d0d5d9",
                        "background": "#f5f7fa",
                        "tertiary-dim": "#595000",
                        "on-secondary-fixed": "#00480f",
                        "surface": "#f5f7fa",
                        "on-secondary-container": "#005e17",
                        "tertiary": "#665c00",
                        "tertiary-fixed-dim": "#f0dc2b",
                        "surface-container-lowest": "#ffffff",
                        "on-secondary": "#d1ffc8",
                        "surface-container-low": "#eef1f4",
                        "outline-variant": "#abadb0",
                        "surface-variant": "#d9dde1",
                        "secondary-container": "#91f78e",
                        "surface-tint": "#005e9f",
                        "inverse-on-surface": "#9a9da0",
                        "secondary": "#006b1b",
                        "tertiary-container": "#ffeb3b",
                        "secondary-fixed-dim": "#83e881",
                        "on-primary-fixed-variant": "#002e51",
                        "secondary-fixed": "#91f78e",
                        "on-tertiary-fixed-variant": "#6a6000",
                        "on-primary-fixed": "#000000",
                        "on-error-container": "#570008",
                        "inverse-primary": "#2498f5",
                        "on-surface": "#2c2f32",
                        "on-primary": "#edf3ff",
                        "on-error": "#ffefee",
                        "tertiary-fixed": "#ffeb3b",
                        "on-tertiary-container": "#5f5600",
                        "primary-dim": "#00528b",
                        "on-tertiary-fixed": "#4b4400",
                        "error-container": "#fb5151"
                    },
                    borderRadius: {
                        DEFAULT: "1rem",
                        lg: "2rem",
                        xl: "3rem",
                        full: "9999px"
                    },
                    fontFamily: {
                        headline: ["Plus Jakarta Sans"],
                        display: ["Plus Jakarta Sans"],
                        body: ["Be Vietnam Pro"],
                        label: ["Be Vietnam Pro"]
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; background-color: #f5f7fa; color: #2c2f32; }
        h1, h2, h3, h4, h5, h6, .font-headline { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden bg-surface text-on-surface">
    <div class="min-h-screen lg:flex">
        <nav class="w-full border-b border-surface-variant/20 bg-surface-container-low p-6 shadow-[0_4px_24px_rgba(0,0,0,0.02)] lg:fixed lg:left-0 lg:top-0 lg:h-full lg:w-64 lg:border-b-0 lg:border-r">
            <div class="mb-8 pl-2">
                <h2 class="font-headline text-2xl font-bold text-primary">AI-PGAALS</h2>
                <p class="mt-1 text-sm uppercase tracking-wider text-on-surface-variant">Admin Console</p>
            </div>

            <div class="mb-8 flex items-center gap-3 rounded-xl bg-surface-container p-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-on-primary">
                    {{ $adminInitials }}
                </div>
                <div class="overflow-hidden">
                    <p class="truncate text-base font-semibold text-on-surface">{{ $adminUser->name }}</p>
                    <p class="truncate text-sm text-on-surface-variant">System Administrator</p>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <a class="flex items-center gap-3 rounded-lg px-4 py-3 text-on-surface-variant transition-transform transition-colors hover:bg-surface-container active:scale-95" href="{{ route('admin.dashboard') }}">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span class="text-sm uppercase tracking-wide">Overview</span>
                </a>
                <a class="flex items-center gap-3 rounded-lg px-4 py-3 text-on-surface-variant transition-transform transition-colors hover:bg-surface-container active:scale-95" href="{{ route('admin.token-requests.index') }}">
                    <span class="material-symbols-outlined">confirmation_number</span>
                    <span class="text-sm uppercase tracking-wide">Token Requests</span>
                </a>
                <a class="flex items-center gap-3 rounded-lg px-4 py-3 text-on-surface-variant transition-transform transition-colors hover:bg-surface-container active:scale-95" href="{{ route('admin.dashboard') }}#user-management">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">group</span>
                    <span class="text-sm uppercase tracking-wide">User Management</span>
                </a>
                <a class="flex items-center gap-3 rounded-lg bg-surface-container-high px-4 py-3 font-bold text-primary transition-transform transition-colors active:scale-95" href="{{ route('admin.users.trash') }}">
                    <span class="material-symbols-outlined">delete</span>
                    <span class="text-sm uppercase tracking-wide">Trash</span>
                </a>
            </div>

            <div class="mt-8 border-t border-surface-variant/30 pt-6 lg:mt-auto">
                <button class="mb-4 w-full rounded-lg bg-primary px-4 py-3 text-sm font-bold uppercase text-on-primary transition-colors hover:bg-primary-dim">
                    Generate Report
                </button>
                <a class="mb-2 flex items-center gap-3 rounded-lg px-4 py-2 text-on-surface-variant transition-colors hover:bg-surface-container" href="#">
                    <span class="material-symbols-outlined">help</span>
                    <span class="text-sm uppercase tracking-wide">Support</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex w-full items-center gap-3 rounded-lg px-4 py-2 text-on-surface-variant transition-colors hover:bg-surface-container" type="submit">
                        <span class="material-symbols-outlined">logout</span>
                        <span class="text-sm uppercase tracking-wide">Sign Out</span>
                    </button>
                </form>
            </div>
        </nav>

        <div class="w-full lg:ml-64">
            <header class="sticky top-0 z-10 border-b border-surface-variant/15 bg-surface/95 px-6 py-4 backdrop-blur lg:px-8">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h1 class="font-headline text-2xl font-bold tracking-tight text-on-surface lg:text-3xl">Trash</h1>
                        <p class="mt-1 text-sm text-on-surface-variant">Manage deleted users and records.</p>
                    </div>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-end">
                        <div class="relative hidden w-full max-w-xs md:block">
                            <form method="GET" action="{{ route('admin.users.trash') }}">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                                <input name="search" value="{{ $filters['search'] ?? '' }}" class="w-full rounded-full border-none bg-surface-container-low py-2 pl-10 pr-4 text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary" placeholder="Search deleted records..." type="search"/>
                            </form>
                        </div>
                        <div class="flex items-center gap-2 text-on-surface-variant">
                            <a class="rounded-full p-2 transition-colors hover:text-primary" href="#">
                                <span class="material-symbols-outlined">notifications</span>
                            </a>
                            <a class="rounded-full p-2 transition-colors hover:text-primary" href="#">
                                <span class="material-symbols-outlined">help</span>
                            </a>
                            <div class="ml-2 flex h-10 w-10 items-center justify-center rounded-full bg-surface-container-highest text-sm font-bold text-primary">
                                {{ $adminInitials }}
                            </div>
                        </div>
                    </div>
                </div>
            </header>

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
