<x-app-layout>
    <div class="min-h-screen bg-background">
        <header class="sticky top-0 z-30 bg-white/80 shadow-[0_20px_40px_rgba(0,94,159,0.06)] backdrop-blur-xl">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-5 py-5 sm:px-8">
                <div class="flex items-center gap-4">
                    <span class="font-headline text-2xl font-extrabold italic text-blue-600">AI-PGAALS</span>
                    <span class="hidden rounded-full bg-error-container px-3 py-1 text-xs font-bold uppercase tracking-widest text-on-error-container sm:inline-flex">Admin Console</span>
                </div>
                <div class="flex items-center gap-3 rounded-full border border-outline-variant/10 bg-surface-container-low py-1.5 pl-2 pr-4">
                    <span class="material-symbols-outlined text-primary">shield_person</span>
                    <span class="hidden text-sm font-bold text-on-surface sm:inline">{{ Auth::user()->name }}</span>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl space-y-8 px-5 py-8 sm:px-8">
            <section class="rounded-xl bg-inverse-surface p-8 text-white shadow-xl">
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-white/60">Administrator</p>
                <h1 class="mt-3 font-headline text-4xl font-extrabold tracking-tight">System Control Center</h1>
                <p class="mt-4 max-w-3xl text-lg text-white/75">Monitor platform usage, review teacher activity, and keep the learning ecosystem running smoothly.</p>
            </section>

            <section class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Total Users', 'value' => '2,481', 'tone' => 'primary'],
                    ['label' => 'Teachers', 'value' => '84', 'tone' => 'secondary'],
                    ['label' => 'Students', 'value' => '2,397', 'tone' => 'tertiary'],
                    ['label' => 'System Uptime', 'value' => '99.9%', 'tone' => 'on-surface'],
                ] as $card)
                    <div class="rounded-lg border-l-4 border-{{ $card['tone'] }} bg-surface-container-lowest p-6 shadow-[0_10px_30px_rgba(0,0,0,0.02)]">
                        <p class="mb-2 text-xs font-bold uppercase tracking-wider text-on-surface-variant">{{ $card['label'] }}</p>
                        <h3 class="font-headline text-3xl font-bold text-on-surface">{{ $card['value'] }}</h3>
                    </div>
                @endforeach
            </section>

            <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-lg bg-surface-container-lowest p-8 shadow-sm">
                    <h2 class="font-headline text-2xl font-bold">Administrative Actions</h2>
                    <div class="mt-6 space-y-4">
                        <div class="flex items-center justify-between rounded-lg bg-surface-container-low p-4">
                            <span class="font-medium text-on-surface">Review new teacher accounts</span>
                            <button class="rounded-full bg-primary px-4 py-2 text-sm font-bold text-on-primary" type="button">Open Queue</button>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-surface-container-low p-4">
                            <span class="font-medium text-on-surface">Audit system alerts</span>
                            <button class="rounded-full bg-primary px-4 py-2 text-sm font-bold text-on-primary" type="button">Inspect</button>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-surface-container-low p-4">
                            <span class="font-medium text-on-surface">Export platform analytics</span>
                            <button class="rounded-full bg-primary px-4 py-2 text-sm font-bold text-on-primary" type="button">Generate</button>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-primary p-8 text-white shadow-lg shadow-primary/20">
                    <p class="text-xs font-bold uppercase tracking-widest text-white/70">Platform Health</p>
                    <h2 class="mt-3 font-headline text-3xl font-bold">All major systems operational</h2>
                    <p class="mt-4 text-white/80">Authentication, content delivery, and reporting services are healthy. No critical incidents detected in the last 24 hours.</p>
                </div>
            </section>
        </main>
    </div>
</x-app-layout>
