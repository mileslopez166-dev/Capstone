<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>AI-PGAALS Admin Overview</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&family=Be+Vietnam+Pro:wght@400;600&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary-fixed": "#44a5ff",
                        "surface-tint": "#005e9f",
                        "error-dim": "#9f0519",
                        "on-tertiary-container": "#5f5600",
                        "secondary": "#006b1b",
                        "outline": "#74777a",
                        "tertiary-fixed-dim": "#f0dc2b",
                        "inverse-surface": "#0b0f11",
                        "on-primary-container": "#002442",
                        "surface-variant": "#d9dde1",
                        "error-container": "#fb5151",
                        "outline-variant": "#abadb0",
                        "surface-container-lowest": "#ffffff",
                        "primary-fixed-dim": "#2498f5",
                        "surface-container-highest": "#d9dde1",
                        "surface-bright": "#f5f7fa",
                        "on-tertiary-fixed-variant": "#6a6000",
                        "on-primary": "#edf3ff",
                        "surface-container-low": "#eef1f4",
                        "surface-container-high": "#dfe3e7",
                        "secondary-container": "#91f78e",
                        "tertiary-container": "#ffeb3b",
                        "on-background": "#2c2f32",
                        "primary-dim": "#00528b",
                        "on-secondary-fixed-variant": "#00691a",
                        "primary": "#005e9f",
                        "on-error-container": "#570008",
                        "primary-container": "#44a5ff",
                        "on-primary-fixed": "#000000",
                        "inverse-on-surface": "#9a9da0",
                        "tertiary-dim": "#595000",
                        "inverse-primary": "#2498f5",
                        "on-secondary-fixed": "#00480f",
                        "error": "#b31b25",
                        "surface-dim": "#d0d5d9",
                        "surface-container": "#e5e8ec",
                        "secondary-fixed-dim": "#83e881",
                        "on-tertiary-fixed": "#4b4400",
                        "on-secondary-container": "#005e17",
                        "on-error": "#ffefee",
                        "on-primary-fixed-variant": "#002e51",
                        "tertiary": "#665c00",
                        "on-secondary": "#d1ffc8",
                        "tertiary-fixed": "#ffeb3b",
                        "on-surface-variant": "#595c5e",
                        "secondary-fixed": "#91f78e",
                        "secondary-dim": "#005d16",
                        "background": "#f5f7fa",
                        "on-surface": "#2c2f32",
                        "surface": "#f5f7fa",
                        "on-tertiary": "#fff4b0"
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
    <div id="admin-page-shell" class="min-h-screen transition-[filter] duration-200 ease-out lg:flex">
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
                <a id="admin-overview-nav" class="flex items-center gap-3 rounded-lg bg-surface-container-high px-4 py-3 font-bold text-primary transition-transform transition-colors active:scale-95" href="{{ route('admin.dashboard') }}">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">dashboard</span>
                    <span class="text-sm uppercase tracking-wide">Overview</span>
                </a>
                <a class="flex items-center gap-3 rounded-lg px-4 py-3 text-on-surface-variant transition-transform transition-colors hover:bg-surface-container active:scale-95" href="{{ route('admin.token-requests.index') }}">
                    <span class="material-symbols-outlined">confirmation_number</span>
                    <span class="text-sm uppercase tracking-wide">Token Requests</span>
                </a>
                <a id="admin-users-nav" class="flex items-center gap-3 rounded-lg px-4 py-3 text-on-surface-variant transition-transform transition-colors hover:bg-surface-container active:scale-95" href="{{ route('admin.dashboard') }}#user-management">
                    <span class="material-symbols-outlined">group</span>
                    <span class="text-sm uppercase tracking-wide">User Management</span>
                </a>
                <a class="flex items-center gap-3 rounded-lg px-4 py-3 text-on-surface-variant transition-transform transition-colors hover:bg-surface-container active:scale-95" href="{{ route('admin.users.trash') }}">
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
                    <div class="flex items-center gap-4">
                        <h1 class="font-headline text-2xl font-bold tracking-tight text-on-surface lg:text-3xl">AI-PGAALS</h1>
                    </div>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-end">
                        <div class="relative hidden w-full max-w-xs md:block">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                            <input class="w-full rounded-full border-none bg-surface-container-low py-2 pl-10 pr-4 text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary" placeholder="Search..." type="text"/>
                        </div>
                        <div class="flex items-center gap-2 text-on-surface-variant">
                            <button class="rounded-full p-2 transition-colors hover:text-primary" type="button">
                                <span class="material-symbols-outlined">notifications</span>
                            </button>
                            <button class="rounded-full p-2 transition-colors hover:text-primary" type="button">
                                <span class="material-symbols-outlined">history</span>
                            </button>
                            <button class="rounded-full p-2 transition-colors hover:text-primary" type="button">
                                <span class="material-symbols-outlined">admin_panel_settings</span>
                            </button>
                            <div class="ml-2 flex h-10 w-10 items-center justify-center rounded-full bg-surface-container-highest text-sm font-bold text-primary">
                                {{ $adminInitials }}
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-7xl p-6 lg:p-8">
                <header class="mb-10 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 class="font-headline text-4xl font-bold leading-tight text-on-surface">Admin Overview</h1>
                        <p class="mt-2 max-w-2xl text-lg text-on-surface-variant">System-wide health and activity monitoring for the AI-PGAALS platform.</p>
                    </div>
                    <div class="hidden gap-3 lg:flex">
                        <button class="rounded-lg bg-surface-container-low px-5 py-2.5 text-sm font-semibold uppercase tracking-wide text-on-surface transition-colors hover:bg-surface-container" type="button">Export Data</button>
                    </div>
                </header>

                @if (session('status'))
                    <div class="mb-6 rounded-DEFAULT border border-secondary/10 bg-secondary-container/35 px-5 py-4 text-sm font-medium text-on-surface">
                        {{ session('status') }}
                    </div>
                @endif

                <section id="user-management" class="mb-8 scroll-mt-24 rounded-DEFAULT bg-surface-container-lowest shadow-[0_4px_24px_rgba(0,94,159,0.03)]">
                    <div class="border-b border-outline-variant/15 p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <p class="text-sm font-bold uppercase tracking-wider text-primary">User Management</p>
                                <h2 class="mt-2 font-headline text-3xl font-bold text-on-surface">Manage Accounts</h2>
                                <p class="mt-2 max-w-2xl text-sm text-on-surface-variant">Review student, teacher, and administrator accounts from one place.</p>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a class="inline-flex items-center gap-2 rounded-lg bg-surface-container-low px-4 py-2.5 text-sm font-semibold uppercase tracking-wide text-on-surface transition-colors hover:bg-surface-container" href="{{ route('admin.token-requests.index') }}">
                                    <span class="material-symbols-outlined text-[18px]">confirmation_number</span>
                                    Token Requests
                                </a>
                                <a class="inline-flex items-center gap-2 rounded-lg bg-surface-container-low px-4 py-2.5 text-sm font-semibold uppercase tracking-wide text-on-surface transition-colors hover:bg-surface-container" href="{{ route('admin.users.trash') }}">
                                    <span class="material-symbols-outlined text-[18px]">delete_outline</span>
                                    Trash
                                    @if(isset($trashedCount) && $trashedCount > 0)
                                        <span class="ml-2 inline-flex items-center justify-center rounded-full bg-error/10 px-2 py-0.5 text-xs font-semibold text-error">{{ $trashedCount }}</span>
                                    @endif
                                </a>
                                <button id="create-user-open" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-bold uppercase tracking-wide text-on-primary transition-colors hover:bg-primary-dim" type="button">
                                    <span class="material-symbols-outlined text-[18px]">person_add</span>
                                    Create User
                                </button>
                            </div>
                        </div>
                    </div>

                    <form class="flex flex-col gap-4 border-b border-outline-variant/15 bg-surface-container-low p-4 lg:flex-row lg:items-center lg:justify-between" method="GET" action="{{ route('admin.dashboard') }}#user-management">
                        <div class="grid w-full grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">search</span>
                                <input class="w-full rounded-lg border-none bg-surface-container-lowest py-2.5 pl-10 pr-4 text-sm text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary" name="search" placeholder="Search users..." type="search" value="{{ $userFilters['search'] }}">
                            </div>
                            <select class="rounded-lg border-none bg-surface-container-lowest py-2.5 pl-3 pr-8 text-sm font-medium text-on-surface focus:ring-2 focus:ring-primary" name="role">
                                <option value="">All Roles</option>
                                <option value="admin" @selected($userFilters['role'] === 'admin')>Admin</option>
                                <option value="teacher" @selected($userFilters['role'] === 'teacher')>Teacher</option>
                                <option value="student" @selected($userFilters['role'] === 'student')>Student</option>
                            </select>
                            <select class="rounded-lg border-none bg-surface-container-lowest py-2.5 pl-3 pr-8 text-sm font-medium text-on-surface focus:ring-2 focus:ring-primary" name="status">
                                <option value="">All Statuses</option>
                                <option value="approved" @selected($userFilters['status'] === 'approved')>Approved</option>
                                <option value="pending" @selected($userFilters['status'] === 'pending')>Pending</option>
                                <option value="rejected" @selected($userFilters['status'] === 'rejected')>Declined</option>
                            </select>
                            <select class="rounded-lg border-none bg-surface-container-lowest py-2.5 pl-3 pr-8 text-sm font-medium text-on-surface focus:ring-2 focus:ring-primary" name="section">
                                <option value="">All Sections</option>
                                @foreach ($sections as $section)
                                    <option value="{{ $section }}" @selected($userFilters['section'] === $section)>{{ $section }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex shrink-0 gap-3">
                            <a class="inline-flex items-center justify-center rounded-lg bg-surface-container-lowest px-4 py-2.5 text-sm font-semibold text-on-surface-variant transition-colors hover:text-primary" href="{{ route('admin.dashboard') }}#user-management">Clear</a>
                            <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-bold text-on-primary transition-colors hover:bg-primary-dim" type="submit">
                                <span class="material-symbols-outlined text-[18px]">filter_list</span>
                                Filter
                            </button>
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[900px] border-collapse text-left">
                            <thead>
                                <tr class="bg-surface-container-low text-xs uppercase tracking-wider text-on-surface-variant">
                                    <th class="px-6 py-4 font-semibold">User</th>
                                    <th class="px-6 py-4 font-semibold">Role</th>
                                    <th class="px-6 py-4 font-semibold">Section</th>
                                    <th class="px-6 py-4 font-semibold">Status</th>
                                    <th class="px-6 py-4 font-semibold">Joined</th>
                                    <th class="px-6 py-4 text-right font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/15 text-sm">
                                @forelse ($managedUsers as $managedUser)
                                    @php
                                        $initials = \Illuminate\Support\Str::of($managedUser->name)->explode(' ')->filter()->take(2)->map(fn ($segment) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($segment, 0, 1)))->implode('');
                                        $roleClasses = match ($managedUser->role) {
                                            'admin' => 'bg-primary-container/30 text-primary',
                                            'teacher' => 'bg-secondary-container text-secondary-dim',
                                            default => 'bg-surface-container-high text-on-surface-variant',
                                        };
                                        $status = $managedUser->approval_status ?? 'approved';
                                        $statusClasses = match ($status) {
                                            'pending' => 'bg-tertiary-container text-on-tertiary-container',
                                            'rejected' => 'bg-error-container/20 text-error',
                                            default => 'bg-secondary-container text-secondary-dim',
                                        };
                                        $statusLabel = $status === 'rejected' ? 'Declined' : ucfirst($status);
                                        $isSystemAdministrator = $managedUser->isSystemAdministrator();
                                    @endphp
                                    <tr class="transition-colors hover:bg-surface-bright">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-container/30 text-xs font-bold text-primary">
                                                    {{ $initials ?: 'U' }}
                                                </div>
                                                <div>
                                                    <p class="font-bold text-on-surface">{{ $managedUser->name }}</p>
                                                    <p class="text-xs text-on-surface-variant">{{ $managedUser->email }}</p>
                                                    @if($isSystemAdministrator)
                                                        <span class="mt-1 inline-flex rounded-full bg-primary-container/30 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-primary">Fixed System User</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide {{ $roleClasses }}">{{ $managedUser->role }}</span>
                                        </td>
                                        <td class="px-6 py-4 font-medium text-on-surface">{{ $managedUser->section ?: 'Unassigned' }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide {{ $statusClasses }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-on-surface-variant">{{ $managedUser->created_at?->format('M d, Y') ?? 'Unknown' }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="inline-flex items-center gap-3 justify-end">
                                                @if($isSystemAdministrator)
                                                    <span class="inline-flex items-center gap-1 text-sm font-semibold text-on-surface-variant">
                                                        <span class="material-symbols-outlined text-[18px]">lock</span>
                                                        Fixed
                                                    </span>
                                                @else
                                                    <button
                                                        class="inline-flex items-center gap-1 text-sm font-semibold text-primary transition-colors hover:text-primary-dim"
                                                        type="button"
                                                        data-edit-user
                                                        data-user-id="{{ $managedUser->id }}"
                                                        data-update-url="{{ route('admin.users.update', $managedUser) }}"
                                                        data-name="{{ $managedUser->name }}"
                                                        data-email="{{ $managedUser->email }}"
                                                        data-role="{{ $managedUser->role }}"
                                                        data-section="{{ $managedUser->section }}"
                                                        data-status="{{ $managedUser->approval_status ?? 'approved' }}"
                                                        data-gender="{{ $managedUser->gender }}"
                                                        data-initials="{{ $initials ?: 'U' }}"
                                                    >
                                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                                        Edit
                                                    </button>
                                                @endif

                                                @if(! $isSystemAdministrator && $managedUser->role !== 'admin')
                                                    <form method="POST" action="{{ route('admin.users.destroy', $managedUser) }}" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="inline-flex items-center gap-1 text-sm font-semibold text-error transition-colors hover:text-error-dim" type="submit">
                                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                                            Delete
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-6 py-10 text-center text-sm text-on-surface-variant" colspan="6">No users match the selected filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-outline-variant/15 p-4">
                        <div class="flex flex-col gap-4 text-sm text-on-surface-variant lg:flex-row lg:items-center lg:justify-between">
                            <p>
                                Showing {{ $managedUsers->firstItem() ?? 0 }} to {{ $managedUsers->lastItem() ?? 0 }} of {{ $managedUsers->total() }} users
                            </p>
                            <div>
                                {{ $managedUsers->fragment('user-management')->links() }}
                            </div>
                        </div>
                    </div>
                </section>

                <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-12">
                    <div class="col-span-12 grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ($summaryCards as $card)
                            <div class="relative flex flex-col justify-between overflow-hidden rounded-DEFAULT border-l-4 {{ $card['border'] }} bg-surface-container-lowest p-6 shadow-[0_4px_24px_rgba(0,94,159,0.03)]">
                                <div class="relative z-10 mb-4 flex items-start justify-between gap-4">
                                    <div class="rounded-lg {{ $card['icon_bg'] }} p-3 {{ $card['icon_text'] }}">
                                        <span class="material-symbols-outlined">{{ $card['icon'] }}</span>
                                    </div>
                                    <span class="rounded-md px-2 py-1 text-sm font-bold {{ $card['badge']['classes'] }}">{{ $card['badge']['label'] }}</span>
                                </div>
                                <div class="relative z-10">
                                    <h3 class="mb-1 text-sm font-semibold text-on-surface-variant">{{ $card['title'] }}</h3>
                                    <p class="font-headline text-3xl font-bold text-on-surface">{{ $card['value'] }}</p>
                                    <p class="mt-2 text-sm text-on-surface-variant">{{ $card['detail'] }}</p>
                                </div>
                                <div class="absolute -bottom-6 -right-6 h-24 w-24 rounded-full bg-surface-container-high opacity-60 blur-xl"></div>
                            </div>
                        @endforeach
                    </div>

                    <div class="col-span-12 rounded-DEFAULT bg-surface-container-lowest p-6 shadow-[0_4px_24px_rgba(0,94,159,0.03)] lg:col-span-8">
                        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="font-headline text-2xl font-bold text-on-surface">Growth Metrics</h2>
                                <p class="mt-1 text-sm text-on-surface-variant">New accounts created over the last 4 weeks.</p>
                            </div>
                            <select class="rounded-lg border-none bg-surface-container-low py-2 pl-3 pr-8 text-sm font-medium text-on-surface focus:ring-2 focus:ring-primary">
                                <option selected>Last 30 Days</option>
                            </select>
                        </div>

                        @if ($growthSeries->sum('count') === 0)
                            <div class="flex h-64 items-center justify-center rounded-xl border border-dashed border-outline-variant/30 bg-surface-container-low text-center text-sm text-on-surface-variant">
                                No registration activity yet. New users will appear here automatically.
                            </div>
                        @else
                            <div class="relative mt-4 h-64 w-full">
                                <div class="absolute left-0 top-0 flex h-full flex-col justify-between pb-6 pr-2 text-xs text-outline">
                                    @foreach ($growthYAxis as $label)
                                        <span>{{ number_format($label) }}</span>
                                    @endforeach
                                </div>
                                <div class="absolute left-8 right-0 top-0 flex h-[calc(100%-1.5rem)] flex-col justify-between">
                                    @foreach ($growthYAxis as $label)
                                        <div class="w-full border-b {{ $loop->last ? 'border-outline-variant/30' : 'border-outline-variant/15' }}"></div>
                                    @endforeach
                                </div>
                                <div class="relative z-10 flex h-full items-end justify-between gap-2 pb-6 pl-8">
                                    @foreach ($growthSeries as $point)
                                        <div class="group flex h-full w-full flex-col justify-end">
                                            <div class="relative rounded-t-sm bg-primary/20 transition-colors group-hover:bg-primary/30" style="height: {{ $point['height'] }}%">
                                                <div class="absolute inset-x-0 top-0 h-1 bg-primary transition-colors group-hover:bg-primary-fixed"></div>
                                                <div class="absolute -top-9 left-1/2 hidden -translate-x-1/2 rounded-md bg-on-surface px-2 py-1 text-xs text-surface shadow-lg group-hover:block">
                                                    {{ number_format($point['count']) }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="absolute bottom-0 left-8 right-0 flex justify-between pt-2 text-xs text-outline">
                                    @foreach ($growthSeries as $point)
                                        <span title="{{ $point['range'] }}">{{ $point['label'] }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="col-span-12 flex flex-col rounded-DEFAULT bg-surface-container-lowest p-6 shadow-[0_4px_24px_rgba(0,94,159,0.03)] lg:col-span-4">
                        <div class="mb-6 flex items-center justify-between">
                            <div>
                                <h2 class="font-headline text-2xl font-bold text-on-surface">Activity Feed</h2>
                                <p class="mt-1 text-sm text-on-surface-variant">Recent system events and content updates.</p>
                            </div>
                            <button class="text-sm font-semibold text-primary transition-colors hover:text-primary-dim" type="button">View All</button>
                        </div>

                        <div class="flex flex-1 flex-col gap-5 pr-1">
                            @forelse ($activityFeed as $item)
                                <div class="flex gap-4">
                                    <div class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $item['icon_bg'] }}">
                                        <span class="material-symbols-outlined text-sm {{ $item['icon_text'] }}">{{ $item['icon'] }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm text-on-surface">
                                            <span class="font-semibold text-on-surface">{{ $item['title'] }}</span>
                                            {{ $item['description'] }}
                                        </p>
                                        <p class="mt-1 text-xs text-on-surface-variant">{{ $item['time'] }}</p>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-outline-variant/30 bg-surface-container-low px-4 py-5 text-sm text-on-surface-variant">
                                    No recent activity yet. This feed will populate automatically as users and assessments are added.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="col-span-12 grid grid-cols-1 gap-6 rounded-lg bg-surface-container-low p-2 md:grid-cols-2">
                        <div class="rounded-DEFAULT bg-surface-container-lowest p-6 shadow-sm">
                            <h3 class="mb-6 font-headline text-2xl font-bold text-on-surface">User Role Distribution</h3>
                            <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                                <div class="relative h-24 w-24 shrink-0">
                                    <div class="h-full w-full rounded-full" style="{{ $roleDonutStyle }}"></div>
                                    <div class="absolute inset-[10px] flex items-center justify-center rounded-full bg-surface-container-lowest">
                                        <span class="font-headline text-lg font-bold text-on-surface">{{ number_format($totalUsers) }}</span>
                                    </div>
                                </div>
                                <div class="flex-1 space-y-3">
                                    @foreach ($roleDistribution as $role)
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="flex items-center gap-2">
                                                <span class="h-3 w-3 rounded-full" style="background-color: {{ $role['color'] }}"></span>
                                                {{ $role['label'] }}
                                            </span>
                                            <span class="font-semibold">{{ number_format($role['count']) }} ({{ rtrim(rtrim(number_format($role['percentage'], 1), '0'), '.') }}%)</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="rounded-DEFAULT bg-surface-container-lowest p-6 shadow-sm">
                            <div class="mb-6 flex items-center justify-between">
                                <h3 class="font-headline text-2xl font-bold text-on-surface">Assessment Pipeline</h3>
                                <span class="rounded-md bg-secondary-container/40 px-2 py-1 text-sm font-semibold text-secondary">Live Data</span>
                            </div>
                            <div class="space-y-4">
                                @foreach ($assessmentPipeline as $metric)
                                    <div>
                                        <div class="mb-1 flex items-center justify-between text-sm">
                                            <span class="font-medium text-on-surface">{{ $metric['label'] }}</span>
                                            <span class="text-on-surface-variant">{{ number_format($metric['value']) }} / {{ number_format($metric['total']) }}</span>
                                        </div>
                                        <div class="h-2 w-full overflow-hidden rounded-full bg-surface-container-high">
                                            <div class="h-full rounded-full {{ $metric['bar_class'] }}" style="width: {{ $metric['percentage'] }}%"></div>
                                        </div>
                                        <p class="mt-1 text-xs text-on-surface-variant">{{ $metric['badge'] }} - {{ $metric['percentage'] }}%</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <div id="create-user-modal" class="fixed inset-0 z-50 hidden items-center justify-center px-4 py-6 opacity-0 transition-opacity duration-200 ease-out sm:px-6" aria-labelledby="create-user-modal-title" aria-modal="true" role="dialog">
        <button id="create-user-backdrop" class="absolute inset-0 bg-on-surface/35 opacity-0 backdrop-blur-sm transition-opacity duration-200 ease-out" type="button" aria-label="Close create user modal"></button>

        <section id="create-user-panel" class="relative max-h-[90vh] w-full max-w-3xl translate-y-4 scale-[0.98] overflow-y-auto rounded-DEFAULT bg-surface-container-lowest opacity-0 shadow-[0_30px_90px_rgba(0,46,81,0.28)] transition duration-200 ease-out">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-outline-variant/15 bg-surface-container-lowest px-6 py-5">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-primary">User Management</p>
                    <h2 id="create-user-modal-title" class="mt-1 font-headline text-2xl font-bold text-on-surface">Create User</h2>
                    <p class="mt-1 text-sm text-on-surface-variant">Add a student, teacher, or administrator account.</p>
                </div>
                <button id="create-user-close" class="rounded-full p-2 text-on-surface-variant transition-colors hover:bg-surface-container-low hover:text-primary" type="button" aria-label="Close create form">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="p-6">
                <form id="create-user-form" class="space-y-6" method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <input name="create_user_form" type="hidden" value="1">

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div class="space-y-4 md:col-span-2">
                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="create-first-name">First Name</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">person</span>
                                    <input class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-4 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="create-first-name" name="first_name" type="text" value="{{ old('create_user_form') ? old('first_name') : '' }}" required>
                                </div>
                                @if(old('create_user_form'))
                                    @error('first_name')
                                        <p class="text-sm text-error">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="create-middle-name">Middle Name</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">person</span>
                                    <input class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-4 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="create-middle-name" name="middle_name" type="text" value="{{ old('create_user_form') ? old('middle_name') : '' }}">
                                </div>
                                @if(old('create_user_form'))
                                    @error('middle_name')
                                        <p class="text-sm text-error">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="create-last-name">Last Name</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">person</span>
                                    <input class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-4 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="create-last-name" name="last_name" type="text" value="{{ old('create_user_form') ? old('last_name') : '' }}" required>
                                </div>
                                @if(old('create_user_form'))
                                    @error('last_name')
                                        <p class="text-sm text-error">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="create-email">Email Address</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">mail</span>
                                <input class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-4 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="create-email" name="email" type="email" value="{{ old('create_user_form') ? old('email') : '' }}" required>
                            </div>
                            @if(old('create_user_form'))
                                @error('email')
                                    <p class="text-sm text-error">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="create-role">Role</label>
                            <div class="relative">
                                <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-outline">badge</span>
                                <select class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-10 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="create-role" name="role" required>
                                    <option value="student" @selected(old('create_user_form') && old('role') === 'student')>Student</option>
                                    <option value="teacher" @selected(old('create_user_form') && old('role') === 'teacher')>Teacher</option>
                                    <option value="admin" @selected(old('create_user_form') && old('role') === 'admin')>Admin</option>
                                </select>
                            </div>
                            @if(old('create_user_form'))
                                @error('role')
                                    <p class="text-sm text-error">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="create-gender">Avatar Style</label>
                            <div class="relative">
                                <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-outline">face</span>
                                <select class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-10 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="create-gender" name="gender">
                                    <option value="">None</option>
                                    <option value="male" @selected(old('create_user_form') && old('gender') === 'male')>Nova Finch - Boys</option>
                                    <option value="female" @selected(old('create_user_form') && old('gender') === 'female')>Lyra Vale - Girls</option>
                                </select>
                            </div>
                            @if(old('create_user_form'))
                                @error('gender')
                                    <p class="text-sm text-error">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="create-section">Section Assignment</label>
                            <div class="relative">
                                <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-outline">class</span>
                                <select class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-10 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="create-section" name="section">
                                    <option value="">Unassigned</option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section }}" @selected(old('create_user_form') && old('section') === $section)>{{ $section }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if(old('create_user_form'))
                                @error('section')
                                    <p class="text-sm text-error">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="create-approval-status">Account Status</label>
                            <div class="relative max-w-md">
                                <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-outline">verified_user</span>
                                <select class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-10 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="create-approval-status" name="approval_status" required>
                                    <option value="approved" @selected(! old('create_user_form') || old('approval_status') === 'approved')>Approved</option>
                                    <option value="pending" @selected(old('create_user_form') && old('approval_status') === 'pending')>Pending</option>
                                    <option value="rejected" @selected(old('create_user_form') && old('approval_status') === 'rejected')>Declined</option>
                                </select>
                            </div>
                            @if(old('create_user_form'))
                                @error('approval_status')
                                    <p class="text-sm text-error">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="create-password">Password</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">lock</span>
                                <input class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-4 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="create-password" name="password" type="password" autocomplete="new-password" required>
                            </div>
                            @if(old('create_user_form'))
                                @error('password')
                                    <p class="text-sm text-error">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="create-password-confirmation">Confirm Password</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">verified_user</span>
                                <input class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-4 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="create-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-outline-variant/15 pt-6 sm:flex-row sm:justify-end">
                        <button id="create-user-cancel" class="rounded-sm border border-outline-variant/30 bg-surface px-6 py-3 text-sm font-bold uppercase tracking-wider text-on-surface-variant transition-colors hover:bg-surface-container-low" type="button">
                            Cancel
                        </button>
                        <button class="rounded-sm bg-primary px-8 py-3 text-sm font-bold uppercase tracking-wider text-on-primary shadow-sm transition-colors hover:bg-primary-dim" type="submit">
                            Create User
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
    <div id="edit-user-modal" class="fixed inset-0 z-50 hidden items-center justify-center px-4 py-6 opacity-0 transition-opacity duration-200 ease-out sm:px-6" aria-labelledby="edit-user-modal-title" aria-modal="true" role="dialog">
        <button id="edit-user-backdrop" class="absolute inset-0 bg-on-surface/35 opacity-0 backdrop-blur-sm transition-opacity duration-200 ease-out" type="button" aria-label="Close edit user modal"></button>

        <section id="edit-user-panel" class="relative max-h-[90vh] w-full max-w-3xl translate-y-4 scale-[0.98] overflow-y-auto rounded-DEFAULT bg-surface-container-lowest opacity-0 shadow-[0_30px_90px_rgba(0,46,81,0.28)] transition duration-200 ease-out">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-outline-variant/15 bg-surface-container-lowest px-6 py-5">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-primary">User Management</p>
                    <h2 id="edit-user-modal-title" class="mt-1 font-headline text-2xl font-bold text-on-surface">Edit Profile</h2>
                    <p class="mt-1 text-sm text-on-surface-variant">Update account details without leaving the dashboard.</p>
                </div>
                <button id="edit-user-close" class="rounded-full p-2 text-on-surface-variant transition-colors hover:bg-surface-container-low hover:text-primary" type="button" aria-label="Close edit form">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="p-6">
                <div class="mb-6 flex flex-col gap-4 rounded-lg bg-surface-container-low p-5 sm:flex-row sm:items-center">
                    <div id="modal-user-initials" class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-primary-container/30 text-xl font-bold text-primary shadow-sm">U</div>
                    <div class="min-w-0">
                        <h3 id="modal-user-name" class="truncate font-headline text-xl font-bold text-on-surface">Selected user</h3>
                        <p id="modal-user-email" class="truncate text-sm text-on-surface-variant">user@example.com</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="mb-6 rounded-lg border border-error/20 bg-error-container/10 px-4 py-3 text-sm text-error">
                        Please review the highlighted fields and try again.
                    </div>
                @endif

                <form id="edit-user-form" class="space-y-6" method="POST" action="">
                    @csrf
                    @method('PATCH')
                    <input id="edit-user-id" name="edit_user_id" type="hidden" value="{{ old('edit_user_id') }}">

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="edit-name">Full Name</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">person</span>
                                <input class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-4 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="edit-name" name="name" type="text" required>
                            </div>
                            @error('name')
                                <p class="text-sm text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="edit-email">Email Address</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">mail</span>
                                <input class="w-full cursor-not-allowed rounded-sm border-none bg-surface-container py-3 pl-12 pr-12 text-on-surface-variant" id="edit-email" type="email" readonly>
                                <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-sm text-outline-variant">lock</span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="edit-role">Role</label>
                            <div class="relative">
                                <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-outline">badge</span>
                                <select class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-10 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="edit-role" name="role">
                                    <option value="student">Student</option>
                                    <option value="teacher">Teacher</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            @error('role')
                                <p class="text-sm text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="edit-section">Section Assignment</label>
                            <div class="relative">
                                <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-outline">class</span>
                                <select class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-10 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="edit-section" name="section">
                                    <option value="">Unassigned</option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section }}">{{ $section }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('section')
                                <p class="text-sm text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="edit-gender">Avatar Style</label>
                            <div class="relative max-w-md">
                                <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-outline">face</span>
                                <select class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-10 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="edit-gender" name="gender">
                                    <option value="">None</option>
                                    <option value="male">Nova Finch - Boys</option>
                                    <option value="female">Lyra Vale - Girls</option>
                                </select>
                            </div>
                            @error('gender')
                                <p class="text-sm text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="edit-approval-status">Account Status</label>
                            <div class="relative max-w-md">
                                <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-outline">verified_user</span>
                                <select class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-10 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="edit-approval-status" name="approval_status">
                                    <option value="approved">Approved</option>
                                    <option value="pending">Pending</option>
                                    <option value="rejected">Declined</option>
                                </select>
                            </div>
                            @error('approval_status')
                                <p class="text-sm text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <div class="rounded-lg bg-surface-container-low p-5">
                                <div class="mb-5">
                                    <h3 class="font-headline text-lg font-bold text-on-surface">Change Password</h3>
                                    <p class="mt-1 text-sm text-on-surface-variant">Leave these fields blank to keep the current password.</p>
                                </div>

                                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="edit-password">New Password</label>
                                        <div class="relative">
                                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">lock</span>
                                            <input class="w-full rounded-sm border-none bg-surface-container-lowest py-3 pl-12 pr-4 text-on-surface shadow-inner transition-colors focus:ring-2 focus:ring-primary" id="edit-password" name="password" type="password" autocomplete="new-password" placeholder="Enter new password">
                                        </div>
                                        @error('password')
                                            <p class="text-sm text-error">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="edit-password-confirmation">Confirm Password</label>
                                        <div class="relative">
                                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">verified_user</span>
                                            <input class="w-full rounded-sm border-none bg-surface-container-lowest py-3 pl-12 pr-4 text-on-surface shadow-inner transition-colors focus:ring-2 focus:ring-primary" id="edit-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Re-enter password">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-outline-variant/15 pt-6 sm:flex-row sm:justify-end">
                        <button id="edit-user-cancel" class="rounded-sm border border-outline-variant/30 bg-surface px-6 py-3 text-sm font-bold uppercase tracking-wider text-on-surface-variant transition-colors hover:bg-surface-container-low" type="button">
                            Cancel
                        </button>
                        <button class="rounded-sm bg-primary px-8 py-3 text-sm font-bold uppercase tracking-wider text-on-primary shadow-sm transition-colors hover:bg-primary-dim" type="submit">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    @php
        $oldEditValues = [
            'name' => old('name'),
            'role' => old('role'),
            'section' => old('section'),
            'gender' => old('gender'),
            'approval_status' => old('approval_status'),
        ];
    @endphp

    <script>
        const overviewNav = document.getElementById('admin-overview-nav');
        const usersNav = document.getElementById('admin-users-nav');
        const pageShell = document.getElementById('admin-page-shell');
        const createUserOpen = document.getElementById('create-user-open');
        const createUserModal = document.getElementById('create-user-modal');
        const createUserBackdrop = document.getElementById('create-user-backdrop');
        const createUserPanel = document.getElementById('create-user-panel');
        const createName = document.getElementById('create-first-name');
        const editUserModal = document.getElementById('edit-user-modal');
        const editUserBackdrop = document.getElementById('edit-user-backdrop');
        const editUserPanel = document.getElementById('edit-user-panel');
        const editUserForm = document.getElementById('edit-user-form');
        const editUserButtons = document.querySelectorAll('[data-edit-user]');
        const modalUserInitials = document.getElementById('modal-user-initials');
        const modalUserName = document.getElementById('modal-user-name');
        const modalUserEmail = document.getElementById('modal-user-email');
        const editUserId = document.getElementById('edit-user-id');
        const editName = document.getElementById('edit-name');
        const editEmail = document.getElementById('edit-email');
        const editRole = document.getElementById('edit-role');
        const editSection = document.getElementById('edit-section');
        const editGender = document.getElementById('edit-gender');
        const editApprovalStatus = document.getElementById('edit-approval-status');
        const editPassword = document.getElementById('edit-password');
        const editPasswordConfirmation = document.getElementById('edit-password-confirmation');
        const oldEditUserId = @json(old('edit_user_id'));
        const oldEditValues = @json($oldEditValues);
        const shouldOpenCreateUserModal = @json((bool) old('create_user_form'));
        let createUserCloseTimer;
        let editUserCloseTimer;

        function setAdminNavState() {
            const userManagementActive = window.location.hash === '#user-management';

            overviewNav.classList.toggle('bg-surface-container-high', !userManagementActive);
            overviewNav.classList.toggle('font-bold', !userManagementActive);
            overviewNav.classList.toggle('text-primary', !userManagementActive);
            overviewNav.classList.toggle('text-on-surface-variant', userManagementActive);
            overviewNav.classList.toggle('hover:bg-surface-container', userManagementActive);
            overviewNav.querySelector('.material-symbols-outlined').style.fontVariationSettings = userManagementActive ? '' : "'FILL' 1";

            usersNav.classList.toggle('bg-surface-container-high', userManagementActive);
            usersNav.classList.toggle('font-bold', userManagementActive);
            usersNav.classList.toggle('text-primary', userManagementActive);
            usersNav.classList.toggle('text-on-surface-variant', !userManagementActive);
            usersNav.classList.toggle('hover:bg-surface-container', !userManagementActive);
            usersNav.querySelector('.material-symbols-outlined').style.fontVariationSettings = userManagementActive ? "'FILL' 1" : '';
        }

        window.addEventListener('hashchange', setAdminNavState);
        setAdminNavState();

        function openCreateUserModal() {
            clearTimeout(createUserCloseTimer);
            createUserModal.classList.remove('hidden');
            createUserModal.classList.add('flex');
            document.body.classList.add('overflow-hidden');

            requestAnimationFrame(() => {
                createUserModal.classList.remove('opacity-0');
                createUserBackdrop.classList.remove('opacity-0');
                createUserPanel.classList.remove('translate-y-4', 'scale-[0.98]', 'opacity-0');
                createUserPanel.classList.add('translate-y-0', 'scale-100', 'opacity-100');
                pageShell.classList.add('blur-sm', 'pointer-events-none', 'select-none');
            });

            setTimeout(() => createName.focus(), 180);
        }

        function closeCreateUserModal() {
            createUserModal.classList.add('opacity-0');
            createUserBackdrop.classList.add('opacity-0');
            createUserPanel.classList.add('translate-y-4', 'scale-[0.98]', 'opacity-0');
            createUserPanel.classList.remove('translate-y-0', 'scale-100', 'opacity-100');
            pageShell.classList.remove('blur-sm', 'pointer-events-none', 'select-none');
            document.body.classList.remove('overflow-hidden');

            createUserCloseTimer = setTimeout(() => {
                createUserModal.classList.add('hidden');
                createUserModal.classList.remove('flex');
            }, 200);
        }
        function openEditUserModal(button, values = {}) {
            const dataset = button.dataset;

            clearTimeout(editUserCloseTimer);
            editUserForm.action = dataset.updateUrl;
            editUserId.value = dataset.userId;
            editName.value = values.name ?? dataset.name ?? '';
            editEmail.value = dataset.email ?? '';
            editRole.value = values.role ?? dataset.role ?? 'student';
            editSection.value = values.section ?? dataset.section ?? '';
            editGender.value = values.gender ?? dataset.gender ?? '';
            editApprovalStatus.value = values.approval_status ?? dataset.status ?? 'approved';
            editPassword.value = '';
            editPasswordConfirmation.value = '';
            modalUserInitials.textContent = dataset.initials || 'U';
            modalUserName.textContent = editName.value || 'Selected user';
            modalUserEmail.textContent = dataset.email || '';

            editUserModal.classList.remove('hidden');
            editUserModal.classList.add('flex');
            document.body.classList.add('overflow-hidden');

            requestAnimationFrame(() => {
                editUserModal.classList.remove('opacity-0');
                editUserBackdrop.classList.remove('opacity-0');
                editUserPanel.classList.remove('translate-y-4', 'scale-[0.98]', 'opacity-0');
                editUserPanel.classList.add('translate-y-0', 'scale-100', 'opacity-100');
                pageShell.classList.add('blur-sm', 'pointer-events-none', 'select-none');
            });

            setTimeout(() => editName.focus(), 180);
        }

        function closeEditUserModal() {
            editUserModal.classList.add('opacity-0');
            editUserBackdrop.classList.add('opacity-0');
            editUserPanel.classList.add('translate-y-4', 'scale-[0.98]', 'opacity-0');
            editUserPanel.classList.remove('translate-y-0', 'scale-100', 'opacity-100');
            pageShell.classList.remove('blur-sm', 'pointer-events-none', 'select-none');
            document.body.classList.remove('overflow-hidden');

            editUserCloseTimer = setTimeout(() => {
                editUserModal.classList.add('hidden');
                editUserModal.classList.remove('flex');
            }, 200);
        }

        createUserOpen.addEventListener('click', openCreateUserModal);
        createUserBackdrop.addEventListener('click', closeCreateUserModal);
        document.getElementById('create-user-close').addEventListener('click', closeCreateUserModal);
        document.getElementById('create-user-cancel').addEventListener('click', closeCreateUserModal);

        editUserButtons.forEach((button) => {
            button.addEventListener('click', () => openEditUserModal(button));
        });

        editUserBackdrop.addEventListener('click', closeEditUserModal);
        document.getElementById('edit-user-close').addEventListener('click', closeEditUserModal);
        document.getElementById('edit-user-cancel').addEventListener('click', closeEditUserModal);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && ! editUserModal.classList.contains('hidden')) {
                closeEditUserModal();
            }
        });

        editName.addEventListener('input', () => {
            modalUserName.textContent = editName.value || 'Selected user';
        });

        if (shouldOpenCreateUserModal) {
            openCreateUserModal();
        }

        if (oldEditUserId) {
            const button = document.querySelector(`[data-edit-user][data-user-id="${oldEditUserId}"]`);

            if (button) {
                openEditUserModal(button, oldEditValues);
            }
        }
    </script>
</body>
</html>