<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Token Requests - Admin Console</title>
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
                <a class="flex items-center gap-3 rounded-lg bg-surface-container-high px-4 py-3 font-bold text-primary transition-transform transition-colors active:scale-95" href="{{ route('admin.token-requests.index') }}">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">confirmation_number</span>
                    <span class="text-sm uppercase tracking-wide">Token Requests</span>
                </a>
                <a class="flex items-center gap-3 rounded-lg px-4 py-3 text-on-surface-variant transition-transform transition-colors hover:bg-surface-container active:scale-95" href="{{ route('admin.dashboard') }}#user-management">
                    <span class="material-symbols-outlined">group</span>
                    <span class="text-sm uppercase tracking-wide">User Management</span>
                </a>
                <a class="flex items-center gap-3 rounded-lg {{ request()->routeIs('admin.users.trash') ? 'bg-surface-container-high font-bold text-primary' : 'text-on-surface-variant hover:bg-surface-container' }} px-4 py-3 transition-transform transition-colors active:scale-95" href="{{ route('admin.users.trash') }}">
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
                <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="font-headline text-4xl font-bold leading-tight text-on-surface">Token Requests</h1>
                    <p class="mt-2 max-w-2xl text-lg text-on-surface-variant">Review student and teacher registration approvals before access is granted.</p>
                </div>
                <div class="hidden gap-3 lg:flex">
                    <button class="rounded-lg bg-surface-container-low px-5 py-2.5 text-sm font-semibold uppercase tracking-wide text-on-surface transition-colors hover:bg-surface-container" type="button">
                        <span class="material-symbols-outlined text-sm">download</span>
                        Export Log
                    </button>
                </div>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-DEFAULT border border-secondary/10 bg-secondary-container/35 px-5 py-4 text-sm font-medium text-on-surface">
                    {{ session('status') }}
                </div>
            @endif

            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="relative overflow-hidden rounded-lg bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,94,159,0.04)]">
                    <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-surface-container-low opacity-50"></div>
                    <div class="relative z-10 flex h-full flex-col justify-between">
                        <div class="mb-4 flex items-start justify-between">
                            <h3 class="font-headline text-2xl font-semibold text-on-surface">Total Pending</h3>
                            <div class="rounded-DEFAULT bg-tertiary-container p-2 text-on-tertiary-container">
                                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">hourglass_top</span>
                            </div>
                        </div>
                        <div>
                            <span class="font-display text-4xl font-extrabold text-on-surface">{{ number_format($pendingCount) }}</span>
                            <p class="mt-1 flex items-center gap-1 text-sm text-on-surface-variant">
                                <span class="material-symbols-outlined {{ $pendingTrend['is_positive'] ? 'text-error' : 'text-secondary' }} text-sm">
                                    {{ $pendingTrend['is_positive'] ? 'arrow_upward' : 'arrow_downward' }}
                                </span>
                                <span class="{{ $pendingTrend['is_positive'] ? 'text-error' : 'text-secondary' }} font-medium">{{ $pendingTrend['label'] }}</span> vs last week
                            </p>
                        </div>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-lg border-l-4 border-secondary bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,94,159,0.04)]">
                    <div class="relative z-10 flex h-full flex-col justify-between">
                        <div class="mb-4 flex items-start justify-between">
                            <h3 class="font-headline text-2xl font-semibold text-on-surface">Approved Today</h3>
                            <div class="rounded-DEFAULT bg-secondary-container p-2 text-on-secondary-container">
                                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                            </div>
                        </div>
                        <div>
                            <span class="font-display text-4xl font-extrabold text-on-surface">{{ number_format($approvedToday) }}</span>
                            <p class="mt-1 text-sm text-on-surface-variant">Student and teacher approvals completed today</p>
                        </div>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-lg bg-primary p-6 text-on-primary shadow-[0_20px_40px_rgba(0,94,159,0.1)]">
                    <div class="absolute -bottom-10 -right-10 h-40 w-40 rounded-full border-[20px] border-primary-dim opacity-50"></div>
                    <div class="relative z-10 flex h-full flex-col justify-between">
                        <div class="mb-4 flex items-start justify-between">
                            <h3 class="font-headline text-2xl font-semibold text-on-primary/90">Approved Accounts</h3>
                            <div class="rounded-DEFAULT bg-primary-dim p-2 text-on-primary">
                                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">database</span>
                            </div>
                        </div>
                        <div>
                            <span class="font-display text-4xl font-extrabold">{{ number_format($approvedAccounts) }}</span>
                            <p class="mt-1 text-sm text-on-primary/80">Student and teacher accounts active in the system</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-DEFAULT bg-surface-container-lowest shadow-[0_20px_40px_rgba(0,94,159,0.02)]">
                <div class="flex items-center justify-between bg-surface-container-low px-6 py-4">
                    <h3 class="font-headline text-2xl font-bold text-on-surface">Pending Requests</h3>
                    <div class="flex gap-2">
                        <button class="rounded-DEFAULT bg-surface p-2 text-on-surface-variant transition-colors hover:text-primary" type="button">
                            <span class="material-symbols-outlined text-sm">filter_list</span>
                        </button>
                        <button class="rounded-DEFAULT bg-surface p-2 text-on-surface-variant transition-colors hover:text-primary" type="button">
                            <span class="material-symbols-outlined text-sm">more_vert</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-outline-variant/15 bg-surface text-sm font-semibold text-on-surface-variant">
                                <th class="px-6 py-4 font-headline text-xs uppercase tracking-wider">Account Name</th>
                                <th class="px-6 py-4 font-headline text-xs uppercase tracking-wider">Section</th>
                                <th class="px-6 py-4 text-right font-headline text-xs uppercase tracking-wider">Request Type</th>
                                <th class="px-6 py-4 font-headline text-xs uppercase tracking-wider">Request Date</th>
                                <th class="px-6 py-4 text-right font-headline text-xs uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/15">
                            @forelse ($pendingRequests as $requestUser)
                                <tr class="transition-colors duration-150 hover:bg-surface-container-low">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-container text-xs font-bold text-on-primary-container">
                                                {{ \Illuminate\Support\Str::of($requestUser->name)->explode(' ')->filter()->take(2)->map(fn ($segment) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($segment, 0, 1)))->implode('') }}
                                            </div>
                                            <div>
                                                <p class="font-medium text-on-surface">{{ $requestUser->name }}</p>
                                                <p class="text-xs text-on-surface-variant">{{ $requestUser->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-on-surface">
                                        @if($requestUser->isTeacher())
                                            <select
                                                class="w-full min-w-36 rounded-sm border-none bg-surface-container-low px-3 py-2 text-sm font-medium text-on-surface focus:ring-2 focus:ring-primary"
                                                form="approve-request-{{ $requestUser->id }}"
                                                name="section"
                                            >
                                                @foreach (["Section A", "Section B", "Section C"] as $section)
                                                    <option value="{{ $section }}" @selected($requestUser->section === $section)>{{ $section }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            {{ $requestUser->section ?: 'Unassigned' }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="inline-flex items-center justify-center rounded-full bg-surface-container px-3 py-1 text-sm font-semibold text-on-surface">
                                            {{ ucfirst($requestUser->role) }} Approval
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-on-surface-variant">{{ $requestUser->created_at?->format('M d, h:i A') }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a class="rounded-DEFAULT px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary-container/20" href="{{ route('admin.users.edit', $requestUser) }}">
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('admin.token-requests.decline', $requestUser) }}">
                                                @csrf
                                                <button class="rounded-DEFAULT px-4 py-2 text-sm font-medium text-error transition-colors hover:bg-error-container/20" type="submit">
                                                    Decline
                                                </button>
                                            </form>
                                            <form id="approve-request-{{ $requestUser->id }}" method="POST" action="{{ route('admin.token-requests.approve', $requestUser) }}">
                                                @csrf
                                                <button class="rounded-DEFAULT bg-secondary px-4 py-2 text-sm font-bold text-surface-container-lowest shadow-sm transition-colors hover:bg-secondary-dim" type="submit">
                                                    Approve
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-6 py-12 text-center text-sm text-on-surface-variant" colspan="5">
                                        No pending account approval requests right now.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between border-t border-outline-variant/15 bg-surface px-6 py-4 text-sm text-on-surface-variant">
                    <span>
                        Showing {{ $pendingRequests->count() > 0 ? $pendingRequests->firstItem() : 0 }} to {{ $pendingRequests->count() > 0 ? $pendingRequests->lastItem() : 0 }} of {{ $pendingRequests->total() }} entries
                    </span>
                    <div class="flex items-center gap-1">
                        @if ($pendingRequests->onFirstPage())
                            <span class="flex h-8 w-8 items-center justify-center rounded-DEFAULT bg-surface-container-low opacity-50">
                                <span class="material-symbols-outlined text-sm">chevron_left</span>
                            </span>
                        @else
                            <a class="flex h-8 w-8 items-center justify-center rounded-DEFAULT bg-surface-container-low transition-colors hover:bg-surface-container" href="{{ $pendingRequests->previousPageUrl() }}">
                                <span class="material-symbols-outlined text-sm">chevron_left</span>
                            </a>
                        @endif

                        <span class="flex h-8 w-8 items-center justify-center rounded-DEFAULT bg-primary font-bold text-on-primary">
                            {{ $pendingRequests->currentPage() }}
                        </span>

                        @if ($pendingRequests->hasMorePages())
                            <a class="flex h-8 w-8 items-center justify-center rounded-DEFAULT bg-surface-container-low transition-colors hover:bg-surface-container" href="{{ $pendingRequests->nextPageUrl() }}">
                                <span class="material-symbols-outlined text-sm">chevron_right</span>
                            </a>
                        @else
                            <span class="flex h-8 w-8 items-center justify-center rounded-DEFAULT bg-surface-container-low opacity-50">
                                <span class="material-symbols-outlined text-sm">chevron_right</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            </main>
        </div>
    </div>
</body>
</html>
