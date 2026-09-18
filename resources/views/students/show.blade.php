<x-app-layout>
    @php
        $teacherName = Auth::user()->name;
        $teacherInitials = collect(explode(' ', $teacherName))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
    @endphp

    <div class="min-h-screen bg-background">
        <div class="lg:ml-72">
            <x-teacher-topbar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" search-placeholder="Search student by name..." />
        </div>

        <div class="flex min-h-screen">
            <x-teacher-sidebar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" active="students" />

            <main class="flex-1 p-4 md:p-8 lg:ml-72 lg:p-12">
                <section class="teacher-workspace-heading mb-12 grid grid-cols-1 items-end gap-8 lg:grid-cols-12">
                    <div class="lg:col-span-8">
                        <a class="mb-4 flex items-center gap-2 font-bold text-primary" href="{{ route('dashboard') }}">
                            <span class="material-symbols-outlined">arrow_back</span>
                            <span class="font-headline text-sm uppercase tracking-widest">Student Directory</span>
                        </a>
                        <h1 class="mb-2 font-headline text-5xl font-extrabold tracking-tight text-on-surface md:text-6xl">{{ $student->name }}</h1>
                        <div class="mt-4 flex flex-wrap items-center gap-4">
                            <span class="rounded-full bg-primary-container px-4 py-1.5 text-sm font-bold text-on-primary-container">Student Account</span>
                            <span class="rounded-full bg-surface-container-high px-4 py-1.5 text-sm font-bold text-on-surface-variant">{{ $studentMetrics['completed_count'] }} completed</span>
                            <span class="flex items-center gap-2 text-sm font-medium text-slate-400">
                                <span class="material-symbols-outlined text-sm">alternate_email</span>{{ $student->email }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 lg:col-span-4">
                        <x-student-pixel-avatar
                            :user="$student"
                            :gender="$student->gender"
                            :name="$student->name"
                            size="lg"
                            :show-card="true"
                            :is-online="$studentIsOnline ?? false"
                            class="w-full"
                        />

                        <div class="w-full rounded-lg border border-outline-variant/10 bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                            <p class="mb-4 text-xs font-bold uppercase tracking-widest text-slate-400">Student Progress</p>
                            <div class="flex items-baseline gap-2">
                                <span class="font-headline text-4xl font-black text-primary">{{ $studentMetrics['average_accuracy'] === null ? 'No Data' : $studentMetrics['average_accuracy'].'%' }}</span>
                            </div>
                            <p class="mt-3 text-sm text-on-surface-variant">{{ $studentMetrics['completed_count'] > 0 ? number_format($studentMetrics['total_points']).' total points from saved submissions.' : 'This student has not completed any recorded assessments yet.' }}</p>
                        </div>
                    </div>
                </section>

                @if (session('status'))<p class="mb-6 rounded-lg bg-secondary-container/30 p-4" role="status">{{ session('status') }}</p>@endif
                <section class="assisted-picker" aria-labelledby="student-assessments-title">
                    <h2 id="student-assessments-title">Take an Assessment Together</h2>
                    @forelse ($assistedAssessments as $assignedAssessment)
                        <div class="assisted-assignment">
                            <div><strong>{{ $assignedAssessment->title }}</strong><p>{{ ucfirst($assignedAssessment->subject) }} &middot; {{ str($assignedAssessment->assessment_type)->replace('_', ' ')->title() }}</p></div>
                            <a class="ui-button" href="{{ route('teacher.assessments.take', [$assignedAssessment, $student]) }}"><span class="material-symbols-outlined" aria-hidden="true">play_arrow</span>Start / Resume</a>
                        </div>
                    @empty
                        <p>No unlocked assessments assigned to this student.</p>
                    @endforelse
                </section>

                <section class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border border-outline-variant/10 bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)] md:col-span-2">
                        <div class="mb-8 flex items-start justify-between">
                            <div>
                                <h3 class="font-headline text-xl font-bold">Score Trends</h3>
                                <p class="text-sm text-on-surface-variant">{{ $submissions->isEmpty() ? 'No completed activities have been recorded for this student.' : 'Recent completed assessments from this teacher.' }}</p>
                            </div>
                            <span class="material-symbols-outlined text-primary">trending_up</span>
                        </div>
                        <div class="flex min-h-[12rem] flex-col items-center justify-center rounded-xl bg-surface-container-low p-6 text-center">
                            <span class="material-symbols-outlined text-5xl text-outline-variant">show_chart</span>
                            <p class="mt-4 font-headline text-xl font-bold text-on-surface">No score history yet</p>
                        </div>
                    </div>

                    <div class="flex flex-col justify-between rounded-lg border border-outline-variant/10 bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                        <div>
                            <h3 class="font-headline text-xl font-bold">Accuracy</h3>
                            <p class="text-sm text-on-surface-variant">Awaiting first submitted answers</p>
                        </div>
                        <div class="flex flex-col items-center py-4">
                            <div class="relative flex h-32 w-32 items-center justify-center">
                                <svg class="h-full w-full -rotate-90">
                                    <circle class="text-surface-container-highest" cx="64" cy="64" r="56" fill="transparent" stroke="currentColor" stroke-width="12"></circle>
                                    <circle class="text-secondary" cx="64" cy="64" r="56" fill="transparent" stroke="currentColor" stroke-width="12" stroke-dasharray="351.85" stroke-dashoffset="351.85"></circle>
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-3xl font-black text-on-surface">{{ $studentMetrics['average_accuracy'] === null ? '0%' : $studentMetrics['average_accuracy'].'%' }}</span>
                                    <span class="text-[10px] font-bold uppercase text-on-surface-variant">Average</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="teacher-callout relative flex flex-col justify-between overflow-hidden rounded-lg bg-primary p-8 text-on-primary shadow-xl">
                        <div>
                            <div class="mb-2 flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">bolt</span>
                                <span class="text-xs font-bold uppercase tracking-tighter">Teacher Action</span>
                            </div>
                            <h3 class="font-headline text-xl font-bold leading-tight">Assign an assessment first</h3>
                        </div>
                        <p class="mt-4 text-sm leading-relaxed text-on-primary/80">Recommendations will only become meaningful after this student completes teacher-prepared assessments.</p>
                        <a class="mt-6 block w-full rounded-sm bg-white px-4 py-2 text-center text-sm font-bold text-primary" href="{{ route('reports.index') }}">Open Reports</a>
                    </div>
                </section>


                <section class="mt-8 rounded-lg border border-outline-variant/10 bg-surface-container-lowest p-6 shadow-sm sm:p-8">
                    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                        <div>
                            <h2 class="font-headline text-2xl font-bold">Assessment Requests</h2>
                            <p class="mt-1 text-sm text-on-surface-variant">Approve retake tokens and set how many tries this student can use.</p>
                        </div>
                    </div>

                    @if (($assessmentRequests ?? collect())->isEmpty())
                        <div class="rounded-xl bg-surface-container-low p-6 text-center">
                            <span class="material-symbols-outlined text-5xl text-outline-variant">lock_reset</span>
                            <p class="mt-3 font-headline text-xl font-bold text-on-surface">No retake requests</p>
                            <p class="mt-2 text-sm text-on-surface-variant">When this student asks to take an assessment again, the request will appear here.</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($assessmentRequests as $requestItem)
                                <div class="rounded-xl border border-outline-variant/10 bg-surface-container-low p-4">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                        <div>
                                            <p class="font-headline text-lg font-bold text-on-surface">{{ $requestItem->assessment?->title ?? 'Assessment' }}</p>
                                            <p class="mt-1 text-xs font-bold uppercase tracking-widest text-on-surface-variant">
                                                {{ ucfirst($requestItem->status) }} | Requested {{ $requestItem->requested_tries }} {{ Str::plural('try', $requestItem->requested_tries) }}
                                                @if ($requestItem->status === 'approved')
                                                    | {{ $requestItem->remaining_tries }} remaining
                                                @endif
                                            </p>
                                            @if ($requestItem->message)
                                                <p class="mt-2 text-sm text-on-surface-variant">{{ $requestItem->message }}</p>
                                            @endif
                                        </div>

                                        @if ($requestItem->status === 'pending')
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                                <form class="flex items-center gap-2" method="POST" action="{{ route('students.assessment-requests.approve', [$student, $requestItem]) }}">
                                                    @csrf
                                                    <label class="sr-only" for="approved-tries-{{ $requestItem->id }}">Allowed tries</label>
                                                    <input class="w-20 rounded-sm border-none bg-white px-3 py-2 text-sm font-bold text-on-surface shadow-inner focus:ring-2 focus:ring-primary" id="approved-tries-{{ $requestItem->id }}" name="approved_tries" type="number" min="1" max="10" value="{{ $requestItem->requested_tries }}">
                                                    <button class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-on-primary" type="submit">Approve</button>
                                                </form>
                                                <form method="POST" action="{{ route('students.assessment-requests.decline', [$student, $requestItem]) }}">
                                                    @csrf
                                                    <button class="rounded-lg bg-surface-container-high px-4 py-2 text-sm font-bold text-on-surface-variant" type="submit">Decline</button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <div class="space-y-6 lg:col-span-2">
                        <div class="mb-2 flex items-center justify-between">
                            <h2 class="font-headline text-2xl font-bold">Completed Activities</h2>
                        </div>

                        @if ($submissions->isEmpty())
                            <div class="rounded-lg border border-outline-variant/10 bg-surface-container-lowest p-8 text-center shadow-sm">
                                <span class="material-symbols-outlined text-5xl text-outline-variant">assignment</span>
                                <p class="mt-4 font-headline text-xl font-bold text-on-surface">No completed activities yet</p>
                                <p class="mt-2 text-sm text-on-surface-variant">This student page will begin to reflect real progress after assessments are assigned and submitted.</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach ($submissions as $submission)
                                    @php
                                        $accuracy = $submission->question_count > 0 ? (int) round(($submission->correct_count / $submission->question_count) * 100) : 0;
                                    @endphp
                                    <div class="rounded-lg border border-outline-variant/10 bg-surface-container-lowest p-5 shadow-sm">
                                        <div class="flex items-center justify-between gap-4">
                                            <div>
                                                <p class="font-headline text-lg font-bold text-on-surface">{{ $submission->assessment?->title ?? 'Assessment' }}</p>
                                                <p class="text-sm text-on-surface-variant">{{ $submission->submitted_at?->format('M d, Y') ?? 'Saved result' }}</p>
                                                <a class="practice-link" href="{{ route('teacher.practice.create', $submission) }}"><span class="material-symbols-outlined" aria-hidden="true">flag</span> Assign practice</a>
                                                @if ($submission->assessment?->subject === 'literacy')
                                                    <a class="practice-link" href="{{ route('teacher.phil-iri.show', $submission) }}"><span class="material-symbols-outlined" aria-hidden="true">rule</span>Phil-IRI scoring</a>
                                                @endif
                                            </div>
                                            <span class="rounded-full bg-secondary-container/40 px-3 py-1 text-xs font-black text-secondary-dim">{{ $submission->question_count > 0 ? $accuracy.'%' : 'Reading activity' }}</span>
                                        </div>
                                        <x-phil-iri-result :result="\App\Support\PhilIri::forSubmission($submission)" />
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="space-y-6">
                        <h2 class="font-headline text-2xl font-bold">Skill Breakdown</h2>
                        <div class="rounded-lg border border-outline-variant/10 bg-surface-container-lowest p-8 shadow-sm">
                            <div class="space-y-4">
                                @forelse ($subjectBreakdown as $area)
                                    <div>
                                        <div class="mb-2 flex justify-between text-xs font-bold uppercase tracking-widest text-on-surface-variant">
                                            <span>{{ $area['label'] }}</span>
                                            <span>{{ $area['accuracy'] === null ? 'No Data' : $area['accuracy'].'%' }}</span>
                                        </div>
                                        <div class="h-3 overflow-hidden rounded-full bg-surface-container-high">
                                            <div class="h-full rounded-full bg-primary" style="width: {{ $area['accuracy'] ?? 0 }}%"></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-on-surface-variant">No skill data yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
