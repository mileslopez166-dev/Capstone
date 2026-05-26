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
                <a class="flex items-center gap-3 rounded-lg bg-surface-container-high px-4 py-3 font-bold text-primary transition-transform transition-colors active:scale-95" href="{{ route('admin.dashboard') }}">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">dashboard</span>
                    <span class="text-sm uppercase tracking-wide">Overview</span>
                </a>
                <a class="flex items-center gap-3 rounded-lg px-4 py-3 text-on-surface-variant transition-transform transition-colors hover:bg-surface-container active:scale-95" href="{{ route('admin.token-requests.index') }}">
                    <span class="material-symbols-outlined">confirmation_number</span>
                    <span class="text-sm uppercase tracking-wide">Token Requests</span>
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
                                        <p class="mt-1 text-xs text-on-surface-variant">{{ $metric['badge'] }} · {{ $metric['percentage'] }}%</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
