<x-app-layout>
    @php
        $student = Auth::user();
        $breakdown = [
            ['label' => 'Completed Assessments', 'value' => '0', 'tone' => 'text-secondary'],
            ['label' => 'Saved Results', 'value' => '0', 'tone' => 'text-primary'],
            ['label' => 'Rewards Available', 'value' => 'None', 'tone' => 'text-tertiary'],
        ];
    @endphp

    <div class="min-h-screen overflow-x-hidden bg-background font-body text-on-surface">
        <nav class="sticky top-0 z-50 bg-white/80 shadow-[0_20px_40px_rgba(0,94,159,0.06)] backdrop-blur-xl">
            <div class="mx-auto flex w-full items-center justify-between px-6 py-4">
                <div class="text-2xl font-extrabold italic text-blue-600">AI-PGAALS</div>

                <div class="hidden items-center gap-8 md:flex">
                    <a class="font-medium font-headline text-slate-500 transition-colors hover:text-blue-500 dark:text-slate-400 dark:hover:text-blue-300" href="{{ route('student.dashboard') }}">Home</a>
                    <a class="font-medium font-headline text-slate-500 transition-colors hover:text-blue-500 dark:text-slate-400 dark:hover:text-blue-300" href="{{ route('student.activities') }}">Activities</a>
                    <a class="border-b-4 border-blue-500 font-bold font-headline text-blue-700 transition-colors hover:text-blue-500 dark:text-blue-300 dark:hover:text-blue-300" href="{{ route('student.rewards') }}">Rewards</a>
                    <a class="font-medium font-headline text-slate-500 transition-colors hover:text-blue-500 dark:text-slate-400 dark:hover:text-blue-300" href="{{ route('profile.edit') }}">Profile</a>
                </div>

                <div class="flex items-center gap-4">
                    <button class="text-on-surface-variant transition-colors hover:text-primary" type="button">
                        <span class="material-symbols-outlined">notifications</span>
                    </button>
                    <a class="text-on-surface-variant transition-colors hover:text-primary" href="{{ route('profile.edit') }}">
                        <span class="material-symbols-outlined">account_circle</span>
                    </a>
                </div>
            </div>
        </nav>

        <main class="relative mx-auto max-w-6xl px-4 pb-32 pt-8 md:pt-16">
            <div class="pointer-events-none absolute inset-0 opacity-15" style="background-image: radial-gradient(circle, #44a5ff 10%, transparent 10.5%), radial-gradient(circle, #ffeb3b 10%, transparent 10.5%), radial-gradient(circle, #91f78e 10%, transparent 10.5%); background-size: 40px 40px, 60px 60px, 50px 50px; background-position: 0 0, 20px 30px, 40px 10px;"></div>

            <section class="relative z-10 grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
                <div class="mb-4 text-center lg:col-span-12">
                    <h1 class="mb-2 font-headline text-5xl font-extrabold tracking-tight text-primary md:text-7xl">Rewards</h1>
                    <p class="text-xl font-medium text-on-surface-variant md:text-2xl">This page will show rewards and summaries when real activity results are available.</p>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:col-span-7 md:grid-cols-2">
                    <div class="group relative overflow-hidden rounded-lg bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                        <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-primary-container/20 transition-transform duration-500 group-hover:scale-125"></div>
                        <div class="relative">
                            <span class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Accuracy Score</span>
                            <div class="mt-4 flex items-baseline gap-2">
                                <span class="font-headline text-6xl font-black text-primary">0</span>
                                <span class="font-headline text-2xl font-bold text-primary-dim">%</span>
                            </div>
                            <div class="mt-6 h-4 w-full overflow-hidden rounded-full bg-surface-container-highest">
                                <div class="relative h-full w-[0%] rounded-full bg-secondary shadow-[inset_0_2px_4px_rgba(0,0,0,0.1)]">
                                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="group relative overflow-hidden rounded-lg bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                        <div class="absolute -bottom-4 -left-4 h-20 w-20 rounded-full bg-tertiary-container/30 transition-transform duration-500 group-hover:scale-110"></div>
                        <div class="relative">
                            <span class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Saved Results</span>
                            <div class="mt-4 flex items-center gap-3">
                                <span class="material-symbols-outlined text-4xl text-tertiary">folder</span>
                                <span class="font-headline text-6xl font-black text-on-surface">0</span>
                            </div>
                            <p class="mt-4 text-sm font-medium text-on-surface-variant">No reward totals have been generated yet.</p>
                        </div>
                    </div>

                    <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-primary to-primary-dim p-10 text-on-primary shadow-2xl md:col-span-2">
                        <img class="absolute inset-0 h-full w-full object-cover opacity-30 mix-blend-overlay" alt="Achievement burst" src="https://lh3.googleusercontent.com/aida-public/AB6AXuC7BTUqvGq-IdXSn8OHJ6ckGPNIUxTnrUcsJbdoQzC_P2nA-k6_nt6Jabi5OQyc6TyMQYucqLlzSxpoBFS-AeF2w2BgVzSYyuQj1AWwitBk3jkOv9e90KcHg8ctlo6LAIOTDhmJYJkfhKHf0hUA4e7jnsA8ETXgRlipVoyXNctZH2ZwKG-WP-s3BFbFmuZ2A69cLRppnfi3HbtE3s3Y6bSO8IWKw0Q2RbO-Gh5YA8DDt5DNUsTitrNryKxfeS3Udt1nAuY_f85DNmNi">
                        <div class="relative flex flex-col items-center justify-between gap-8 md:flex-row">
                            <div class="text-center md:text-left">
                                <h3 class="mb-2 font-headline text-3xl font-bold">No Rewards Yet</h3>
                                <p class="max-w-sm text-lg text-on-primary/80">Once the system records completed activities, this section will display available rewards and recognition.</p>
                            </div>
                            <div class="relative flex h-40 w-40 items-center justify-center">
                                <div class="absolute inset-0 rounded-full bg-tertiary-container opacity-40 blur-2xl"></div>
                                <div class="relative flex h-32 w-32 items-center justify-center rounded-full border-4 border-tertiary-container bg-white/10 shadow-inner backdrop-blur-md">
                                    <span class="material-symbols-outlined text-7xl text-tertiary-container">backpack</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6 lg:col-span-5">
                    <div class="rounded-lg bg-surface-container-low p-8">
                        <h4 class="mb-6 flex items-center gap-2 font-headline text-xl font-bold">
                            <span class="material-symbols-outlined text-secondary">assessment</span>
                            Performance Breakdown
                        </h4>
                        <div class="space-y-4">
                            @foreach ($breakdown as $item)
                                <div class="flex items-center justify-between rounded-md bg-surface-container-lowest p-4">
                                    <span class="font-medium text-on-surface-variant">{{ $item['label'] }}</span>
                                    <span class="font-headline font-bold {{ $item['tone'] }}">{{ $item['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-col gap-4">
                        <a class="w-full rounded-lg bg-gradient-to-r from-primary to-primary-container py-5 text-center font-headline text-xl font-extrabold text-on-primary shadow-lg transition-transform duration-200 hover:scale-[1.02] active:scale-95" href="{{ route('student.activities') }}">
                            View Activities
                        </a>
                        <button class="w-full rounded-lg bg-surface-container-highest py-5 font-headline text-lg font-bold text-on-surface-variant transition-colors duration-200 hover:bg-surface-variant active:scale-95" type="button">
                            No Reward Data
                        </button>
                    </div>
                </div>
            </section>
        </main>

        <footer class="fixed bottom-0 left-0 z-50 flex w-full items-center justify-around bg-white/80 px-4 pb-6 pt-4 shadow-[0_-10px_30px_rgba(0,0,0,0.05)] backdrop-blur-2xl md:hidden dark:bg-slate-900/80">
            <a class="flex flex-col items-center justify-center px-4 py-2 text-slate-400 transition-transform hover:scale-105 dark:text-slate-500" href="{{ route('student.dashboard') }}">
                <span class="material-symbols-outlined">home</span>
                <span class="text-[10px] font-bold lowercase">Home</span>
            </a>
            <a class="flex flex-col items-center justify-center px-4 py-2 text-slate-400 transition-transform hover:scale-105 dark:text-slate-500" href="{{ route('student.activities') }}">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">rocket_launch</span>
                <span class="text-[10px] font-bold lowercase">activities</span>
            </a>
            <div class="flex scale-110 flex-col items-center justify-center rounded-[2rem] bg-blue-100 px-6 py-2 text-blue-700 shadow-inner dark:bg-blue-900/40 dark:text-blue-300">
                <span class="material-symbols-outlined">backpack</span>
                <span class="text-[10px] font-bold lowercase">rewards</span>
            </div>
            <a class="flex flex-col items-center justify-center px-4 py-2 text-slate-400 transition-transform hover:scale-105 dark:text-slate-500" href="{{ route('profile.edit') }}">
                <span class="material-symbols-outlined">face</span>
                <span class="text-[10px] font-bold lowercase">Profile</span>
            </a>
        </footer>
    </div>
</x-app-layout>
