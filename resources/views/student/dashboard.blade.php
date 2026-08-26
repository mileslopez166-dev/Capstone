<x-app-layout>
    @php
        $student = Auth::user();
        $firstName = str($student->name)->before(' ')->title();
    @endphp

    <div class="min-h-screen bg-background font-body text-on-surface">
        <x-student-nav active="home" />

        <main class="mx-auto max-w-7xl px-6 py-8 pb-32">
            <section class="relative mb-12 overflow-visible">
                <div class="flex flex-col items-center justify-between gap-8 rounded-lg bg-gradient-to-br from-primary to-primary-container p-8 text-on-primary shadow-xl md:flex-row md:p-12">
                    <div class="flex-1">
                        <h1 class="font-headline text-4xl font-extrabold tracking-tight md:text-5xl">
                            Welcome, {{ $firstName }}
                        </h1>
                        <p class="mb-8 mt-4 max-w-md text-lg text-on-primary/90">Your student dashboard is ready. Open assigned activities, check your account, and review your current status here.</p>

                        <div class="space-y-3">
                            <div class="flex items-end justify-between">
                                <span class="font-headline text-xl font-bold">Assigned Activities</span>
                                <span class="font-bold">2 Available</span>
                            </div>
                            <div class="h-6 w-full overflow-hidden rounded-full border-2 border-white/20 bg-surface-container-highest/30">
                                <div class="relative h-full w-[20%] rounded-full bg-secondary shadow-[inset_0_2px_4px_rgba(255,255,255,0.4)]">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/10 to-transparent"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="relative h-48 w-48 flex-shrink-0">
                        <div class="absolute inset-0 rounded-full bg-white/20 blur-3xl"></div>
                        <img
                            alt="Student Avatar"
                            class="relative z-10 h-full w-full object-contain"
                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuB0Jq6GePF12X-juwqidKkpHeUsIezPA8AjQvdumN_cQHkXgJdwLV5nqxATQLJgze7NG3khpSjJs2IpbKvRkc1mJSI19CsS1tosXcEtlrqyN-UV9zY85OwKKKV4_Plcmfu0VNIJ0klDsVLkjIop9H_bilQyG-k7pd5LfBPtWrNk7nIv1CKfNuh-LOyRUUcElrsZnF_xPZerLZ6je2DTX7wfjrGvhCwJ2qSTp8LWwaYtHw160FVYznA5aCOvaWbFZVG7_Lg8OCRST6Je"
                        />
                    </div>
                </div>
            </section>

            <div class="mb-12 grid grid-cols-1 gap-6 md:grid-cols-12">
                <div class="group rounded-lg border-b-4 border-primary-container bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)] transition-transform hover:-translate-y-1 md:col-span-6">
                    <div class="mb-6 flex items-start justify-between">
                        <div class="rounded-lg bg-primary-container/10 p-4">
                            <span class="material-symbols-outlined text-4xl text-primary">menu_book</span>
                        </div>
                        <span class="rounded-full bg-tertiary-container px-4 py-1 text-sm font-bold tracking-wide text-on-tertiary-container">AVAILABLE</span>
                    </div>
                    <h3 class="font-headline text-2xl font-bold">Phil-IRI Reading</h3>
                    <p class="mb-8 mt-2 text-on-surface-variant">Open the assigned reading assessment prepared by your teacher.</p>
                    <a class="flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-primary to-primary-container py-4 font-bold text-white shadow-lg transition-all hover:shadow-primary/30" href="{{ route('student.activities') }}">
                        Open Activity
                        <span class="material-symbols-outlined">rocket_launch</span>
                    </a>
                </div>

                <div class="group rounded-lg border-b-4 border-secondary-container bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)] transition-transform hover:-translate-y-1 md:col-span-6">
                    <div class="mb-6 flex items-start justify-between">
                        <div class="rounded-lg bg-secondary-container/10 p-4">
                            <span class="material-symbols-outlined text-4xl text-secondary">calculate</span>
                        </div>
                        <span class="rounded-full bg-secondary-container px-4 py-1 text-sm font-bold tracking-wide text-on-secondary-container">PENDING</span>
                    </div>
                    <h3 class="font-headline text-2xl font-bold">ARAL Math</h3>
                    <p class="mb-8 mt-2 text-on-surface-variant">Review the next math activity waiting in your assignment queue.</p>
                    <a class="flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-secondary to-secondary-dim py-4 font-bold text-white shadow-lg transition-all hover:shadow-secondary/30" href="{{ route('student.activities') }}">
                        View Queue
                        <span class="material-symbols-outlined">bolt</span>
                    </a>
                </div>

                <div class="flex flex-col items-center justify-center rounded-lg bg-surface-container-low p-6 text-center md:col-span-4">
                    <span class="mb-2 text-xs font-bold uppercase tracking-widest text-on-surface-variant">Current Status</span>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-4xl text-primary">assignment</span>
                        <span class="font-headline text-3xl font-black text-on-surface">No Summary Yet</span>
                    </div>
                    <p class="mt-4 text-sm text-on-surface-variant">Progress totals will appear here once activity results are saved.</p>
                </div>

                <div class="rounded-lg bg-surface-container-lowest p-6 shadow-sm md:col-span-8">
                    <div class="mb-6 flex items-center justify-between">
                        <h4 class="font-headline text-xl font-bold">Achievements</h4>
                    </div>
                    <div class="rounded-xl bg-surface-container-low p-6 text-center">
                        <span class="material-symbols-outlined text-5xl text-outline-variant">workspace_premium</span>
                        <p class="mt-4 font-headline text-xl font-bold text-on-surface">No rewards available yet</p>
                        <p class="mt-2 text-sm text-on-surface-variant">This area will stay empty until real activity results are recorded by the system.</p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-surface-container-low p-8">
                <h3 class="flex items-center gap-2 font-headline text-xl font-bold">
                    <span class="material-symbols-outlined text-primary">analytics</span>
                    Activity Overview
                </h3>
                <div class="mt-6 rounded-xl bg-surface-container-lowest p-8 text-center">
                    <span class="material-symbols-outlined text-5xl text-outline-variant">bar_chart</span>
                    <p class="mt-4 font-headline text-xl font-bold text-on-surface">No analytics yet</p>
                    <p class="mt-2 text-sm text-on-surface-variant">Charts and summaries will appear here after the system stores completed assessment data.</p>
                </div>
            </div>
        </main>

    </div>
</x-app-layout>
