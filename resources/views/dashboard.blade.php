<x-app-layout>
    @php
        $teacher = Auth::user();
        $teacherName = $teacher->name;
        $teacherInitials = str($teacherName)->explode(' ')->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
        $studentsWithResults = min((int) $dashboardMetrics['students_with_results'], $students->count());
        $completionPercent = $students->isEmpty() ? 0 : (int) round(($studentsWithResults / $students->count()) * 100);
        $needsAttentionStudents = $students->filter(fn (\App\Models\User $student): bool => $student->average_accuracy !== null && $student->average_accuracy < 75)
            ->sortBy(fn (\App\Models\User $student): int => $student->average_accuracy)->values();
        $frustrationCount = $students->filter(fn (\App\Models\User $student): bool => $student->average_accuracy !== null && $student->average_accuracy < 60)->count();
        $instructionalCount = $students->filter(fn (\App\Models\User $student): bool => $student->average_accuracy !== null && $student->average_accuracy >= 60 && $student->average_accuracy < 85)->count();
        $independentCount = $students->filter(fn (\App\Models\User $student): bool => $student->average_accuracy !== null && $student->average_accuracy >= 85)->count();
        $sectionStats = $students->groupBy(fn (\App\Models\User $student): string => $student->section ?: 'Unassigned')
            ->map(function ($sectionStudents, string $section) use ($submissions): array {
                $ids = $sectionStudents->pluck('id');
                $completed = $submissions->whereIn('user_id', $ids)->pluck('user_id')->unique()->count();
                return ['name' => $section, 'total' => $sectionStudents->count(), 'completed' => $completed,
                    'percent' => $sectionStudents->isEmpty() ? 0 : (int) round(($completed / $sectionStudents->count()) * 100)];
            })->values();
        $assessmentFilters = $recentSubmissions->pluck('assessment')->filter()->unique('id')->values();
        $mlInsights = $recentSubmissions->filter(fn ($submission) => $submission->mlPrediction)->take(4);
    @endphp

    <div class="teacher-dashboard-current" data-teacher-dashboard="current">
    <div class="teacher-dashboard min-h-screen">
        <x-teacher-sidebar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" active="dashboard" />
        <main class="min-h-screen lg:ml-72">
            <x-teacher-topbar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" search-placeholder="Search students and assessments..." />
            <div class="teacher-content">
                <div class="teacher-dashboard-bento" x-data="{ section: 'all', assessment: 'all' }">
                    <section class="teacher-welcome-card">
                        <div class="teacher-welcome-decoration" aria-hidden="true"><i></i><i></i><i></i></div>
                        <p class="teacher-dashboard-kicker">{{ now()->format('l, F j, Y') }}</p>
                        <p class="teacher-welcome-grade">Grade 6 <span>·</span> {{ $teacher->section ?: 'All sections' }}</p>
                        <h1>Welcome back,<br>{{ $teacherName }}</h1>
                        <p class="teacher-welcome-subtitle">Your classroom, at a glance.</p>
                        <x-teacher-button href="{{ route('assessments.create') }}" variant="lime" icon="arrow_forward" class="teacher-welcome-action">Create assessment</x-teacher-button>
                    </section>

                    <section class="teacher-student-summary" aria-labelledby="teacher-summary-title">
                        <div class="teacher-bento-heading"><div><p class="teacher-dashboard-kicker">Class snapshot</p><h2 id="teacher-summary-title">Your students</h2></div><span class="teacher-bento-icon material-symbols-outlined" aria-hidden="true">groups</span></div>
                        <div class="teacher-summary-list">
                            <div><span>Total students</span><strong>{{ number_format($students->count()) }} <small>student accounts</small></strong></div>
                            <div><span>With assessment results</span><strong>{{ number_format($studentsWithResults) }}</strong></div>
                            <div class="is-attention"><span>Need support</span><strong>{{ number_format($needsAttentionStudents->count()) }}</strong></div>
                        </div>
                        <div class="teacher-level-summary" aria-label="Student performance levels">
                            <span><i class="is-frustration"></i>Frustration <strong>{{ $frustrationCount }}</strong></span>
                            <span><i class="is-instructional"></i>Instructional <strong>{{ $instructionalCount }}</strong></span>
                            <span><i class="is-independent"></i>Independent <strong>{{ $independentCount }}</strong></span>
                        </div>
                        <a class="teacher-card-link" href="{{ route('students.index') }}">Open student list <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a>
                    </section>

                    <section class="teacher-recommendations" aria-labelledby="teacher-recommendations-title">
                        <div class="teacher-bento-heading">
                            <div><p class="teacher-dashboard-kicker">Action queue</p><h2 id="teacher-recommendations-title">Recommendations &amp; interventions</h2></div>
                            <span class="teacher-count-pill">{{ $needsAttentionStudents->count() }} to review</span>
                        </div>
                        <label class="teacher-filter-field"><span class="material-symbols-outlined" aria-hidden="true">filter_list</span><span class="sr-only">Filter by section</span>
                            <select x-model="section" aria-label="Filter support list by section"><option value="all">All sections</option>@foreach ($sectionStats as $sectionStat)<option value="{{ $sectionStat['name'] }}">{{ $sectionStat['name'] }}</option>@endforeach</select>
                        </label>
                        <div class="teacher-recommendation-list">
                            @forelse ($needsAttentionStudents as $student)
                                @php
                                    $supportTitle = $student->average_accuracy < 60 ? 'Start with supported practice' : 'Reinforce recent skills';
                                    $supportCopy = $student->average_accuracy < 60 ? 'Try shorter activities and review key ideas together.' : 'A brief guided review can help strengthen accuracy.';
                                @endphp
                                <article class="teacher-recommendation-item" data-teacher-section="{{ $student->section ?: 'Unassigned' }}" x-show="section === 'all' || section === $el.dataset.teacherSection">
                                    <span class="teacher-recommendation-icon material-symbols-outlined" aria-hidden="true">{{ $student->average_accuracy < 60 ? 'support' : 'lightbulb' }}</span>
                                    <div class="teacher-recommendation-copy"><div><strong>{{ $student->name }}</strong><x-teacher-badge :tone="$student->average_accuracy < 60 ? 'frustration' : 'instructional'">{{ $student->average_accuracy < 60 ? 'Frustration' : 'Instructional' }}</x-teacher-badge></div><p>{{ $supportTitle }} · {{ $supportCopy }}</p><small>{{ $student->section ?: 'Section not assigned' }} · Average {{ $student->average_accuracy }}%</small></div>
                                    <a class="teacher-icon-action" href="{{ route('students.show', $student) }}" aria-label="View {{ $student->name }}"><span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a>
                                </article>
                            @empty
                                <x-teacher-empty-state icon="task_alt" title="No students flagged" description="Students with recorded results below 75% will appear here." />
                            @endforelse
                        </div>
                        <a class="teacher-card-link" href="{{ route('reports.index') }}">Review interventions <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a>
                    </section>

                    <section class="teacher-completion-card" aria-labelledby="teacher-completion-title">
                        <div class="teacher-bento-heading"><div><p class="teacher-dashboard-kicker">Assessment completion</p><h2 id="teacher-completion-title">Class coverage</h2></div><span class="teacher-bento-icon material-symbols-outlined" aria-hidden="true">fact_check</span></div>
                        <div class="teacher-completion-value"><strong>{{ $completionPercent }}%</strong><span>of students have a result<br>from your assessments</span></div>
                        <div class="teacher-completion-track" role="meter" aria-label="Students with results" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $completionPercent }}"><span style="width: {{ $completionPercent }}%"></span></div>
                        <div class="teacher-completion-counts"><div><strong>{{ number_format($studentsWithResults) }}</strong><span>With results</span></div><div><strong>{{ number_format(max(0, $students->count() - $studentsWithResults)) }}</strong><span>No results yet</span></div><div><strong>{{ number_format($assessmentCount) }}</strong><span>Assessments</span></div></div>
                        <div class="teacher-section-completion">
                            <div class="teacher-section-completion-head"><strong>Subject Progress</strong><label><span class="sr-only">Select section</span><select x-model="section" aria-label="Choose a section"> <option value="all">All sections</option>@foreach ($sectionStats as $sectionStat)<option value="{{ $sectionStat['name'] }}">{{ $sectionStat['name'] }}</option>@endforeach</select></label></div>
                            @foreach ($sectionStats as $sectionStat)
                                <div class="teacher-section-progress" x-show="section === 'all' || section === @js($sectionStat['name'])"><div><span>{{ $sectionStat['name'] }}</span><strong>{{ $sectionStat['completed'] }}/{{ $sectionStat['total'] }}</strong></div><span class="teacher-section-progress-track"><i style="width: {{ $sectionStat['percent'] }}%"></i></span></div>
                            @endforeach
                        </div>
                    </section>

                    <section class="teacher-station" aria-labelledby="teacher-station-title">
                        <div class="teacher-station-orbit" aria-hidden="true"><i></i><i></i><i></i><b>↗</b></div>
                        <p class="teacher-dashboard-kicker">Your workspace</p><h2 id="teacher-station-title">Teacher Station</h2><p>Pick up where your class needs you.</p>
                        <nav aria-label="Teacher quick actions">
                            <a href="{{ route('assessments.create') }}"><span class="material-symbols-outlined" aria-hidden="true">add_circle</span>Create assessment<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a>
                            <a href="{{ route('assessments.results') }}"><span class="material-symbols-outlined" aria-hidden="true">fact_check</span>Review submissions<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a>
                            <a href="{{ route('reports.index') }}"><span class="material-symbols-outlined" aria-hidden="true">monitoring</span>View performance<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a>
                            <a href="{{ route('reports.index') }}"><span class="material-symbols-outlined" aria-hidden="true">summarize</span>Generate report<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a>
                        </nav>
                    </section>

                    <x-teacher-panel class="teacher-recent-panel" aria-labelledby="teacher-recent-title">
                        <x-slot:header><div class="teacher-bento-heading"><div><p class="teacher-dashboard-kicker">Latest activity</p><h2 id="teacher-recent-title">Student Performance Overview</h2><p>Recent submissions from your assessments</p></div><a class="teacher-card-link" href="{{ route('assessments.results') }}">All results <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a></div></x-slot:header>
                        @if ($assessmentFilters->isNotEmpty())
                            <label class="teacher-filter-field teacher-assessment-filter"><span class="material-symbols-outlined" aria-hidden="true">filter_list</span><span class="sr-only">Filter by assessment</span><select x-model="assessment" aria-label="Filter submissions by assessment"><option value="all">All recent assessments</option>@foreach ($assessmentFilters as $filterAssessment)<option value="{{ $filterAssessment->id }}">{{ $filterAssessment->title }}</option>@endforeach</select></label>
                        @endif
                        @forelse ($recentSubmissions as $submission)
                            @php
                                $accuracy = $submission->scorePercentage();
                                $assessment = $submission->assessment;
                                $student = $submission->student;
                                $reviewUrl = $student && $assessment ? ($assessment->subject === 'literacy' ? route('teacher.phil-iri.show', $submission) : route('reports.student', ['student' => $student]).'#submission-'.$submission->id) : route('reports.index');
                            @endphp
                            <a class="teacher-recent-row" href="{{ $reviewUrl }}" x-show="assessment === 'all' || assessment === @js((string) $assessment?->id)">
                                <span class="teacher-recent-icon material-symbols-outlined" aria-hidden="true">{{ $assessment?->subject === 'numeracy' ? 'calculate' : 'auto_stories' }}</span><span class="teacher-recent-copy"><strong>{{ $student?->name ?? 'Student' }}</strong><small>{{ $assessment?->title ?? 'Assessment' }}</small></span><span class="teacher-recent-score">{{ $accuracy === null ? 'Pending' : $accuracy.'%' }}</span>
                            </a>
                        @empty
                            <x-teacher-empty-state icon="inbox" title="No submissions yet" description="Completed student assessments will show here." />
                        @endforelse
                    </x-teacher-panel>

                    <x-teacher-panel class="teacher-insights-panel" aria-labelledby="teacher-insights-title">
                        <x-slot:header><div class="teacher-bento-heading"><div><p class="teacher-dashboard-kicker">Assessment signals</p><h2 id="teacher-insights-title">AI learning insights</h2></div><span class="teacher-bento-icon material-symbols-outlined" aria-hidden="true">psychology</span></div></x-slot:header>
                        <p class="teacher-insights-note">Predictions are teaching support; review them with the student’s assessment evidence.</p>
                        @forelse ($mlInsights as $submission)
                            @php
                                $prediction = $submission->mlPrediction;
                                $philResult = \App\Support\PhilIri::forSubmission($submission);
                                $confidence = $prediction->confidence_score !== null ? round($prediction->confidence_score * 100) : null;
                            @endphp
                            <a class="teacher-insight-row" href="{{ route('reports.student', ['student' => $submission->student]).'#submission-'.$submission->id }}"><span class="teacher-recommendation-icon material-symbols-outlined" aria-hidden="true">insights</span><span><strong>{{ $submission->student?->name ?? 'Student' }}</strong><small>{{ $philResult['label'] ?? 'Level not available' }} · {{ $prediction->prediction }}{{ $confidence !== null ? ' · '.$confidence.'% confidence' : '' }}</small></span><span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a>
                        @empty
                            <x-teacher-empty-state icon="model_training" title="No predictions yet" description="Insights appear when a completed assessment has an available prediction." />
                        @endforelse
                    </x-teacher-panel>
                </div>
            </div>
        </main>
    </div>
    </div>

    <div class="teacher-dashboard-classic" data-teacher-dashboard="classic">
        @include('dashboard-classic')
    </div>
</x-app-layout>
