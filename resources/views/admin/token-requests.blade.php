<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Token Requests - Admin Console</title>
    <x-admin-head />
</head>
<body class="staff-theme admin-theme min-h-screen overflow-x-hidden bg-surface text-on-surface">
    <div class="min-h-screen lg:flex">
        <x-admin-sidebar active="tokens" :admin-user="$adminUser" :admin-initials="$adminInitials" />

        <div class="w-full lg:ml-64">
            <x-admin-topbar title="Token Requests" :admin-user="$adminUser" :admin-initials="$adminInitials" :search-suggestions="$searchSuggestions" :search-action="route('admin.token-requests.index')" search-name="search" :search-value="$filters['search'] ?? ''" search-placeholder="Search token requests..." />

            <main class="mx-auto max-w-7xl p-6 lg:p-8">
                <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="font-headline text-4xl font-bold leading-tight text-on-surface">Token Requests</h1>
                    <p class="mt-2 max-w-2xl text-lg text-on-surface-variant">Review student and teacher registration approvals before access is granted.</p>
                </div>
                <div class="hidden gap-3 lg:flex">
                    <a class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-on-primary" href="{{ route('admin.dashboard') }}#user-management"><span class="material-symbols-outlined text-lg" aria-hidden="true">group</span>Manage accounts</a>
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
                <div class="flex flex-col gap-4 bg-surface-container-low px-6 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="font-headline text-2xl font-bold text-on-surface">Pending Requests</h3>
                        @if (($filters['search'] ?? '') !== '')
                            <p class="mt-1 text-sm text-on-surface-variant">Showing matches for "{{ $filters['search'] }}"</p>
                        @endif
                    </div>
                    <form class="flex w-full max-w-md gap-2" method="GET" action="{{ route('admin.token-requests.index') }}">
                        <div class="relative flex-1">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-sm text-on-surface-variant">search</span>
                            <input class="w-full rounded-DEFAULT border-none bg-surface py-2 pl-9 pr-3 text-sm text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary" list="token-search-suggestions" name="search" placeholder="Search name, email, role, section" type="search" value="{{ $filters['search'] ?? '' }}">
                        </div>
                        @if (($filters['search'] ?? '') !== '')
                            <a class="rounded-DEFAULT bg-surface px-3 py-2 text-sm font-semibold text-on-surface-variant transition-colors hover:text-primary" href="{{ route('admin.token-requests.index') }}">Clear</a>
                        @endif
                        <button class="rounded-DEFAULT bg-primary px-4 py-2 text-sm font-bold text-on-primary transition-colors hover:bg-primary-dim" type="submit">Search</button>
                    </form>
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
                                        {{ ($filters['search'] ?? '') !== '' ? 'No token requests match your search.' : 'No pending account approval requests right now.' }}
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
