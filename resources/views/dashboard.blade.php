<x-app-layout>
    @php
        $teacher = Auth::user();
        $teacherName = $teacher->name;
        $teacherInitials = str($teacherName)->explode(' ')->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
    @endphp
    <div class="teacher-dashboard min-h-screen">
        <x-teacher-sidebar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" active="dashboard" />
        <main class="min-h-screen lg:ml-72">
            <x-teacher-topbar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" search-placeholder="Search students and assessments..." />
            <div class="teacher-content">
                <header class="teacher-page-heading">
                    <div>
                        <div class="teacher-eyebrow"><span class="material-symbols-outlined" aria-hidden="true">calendar_today</span>{{ now()->format('l, F j, Y') }}</div>
                        <h1>Welcome, {{ str($teacherName)->before(' ') }}</h1>
                        <p>Your classroom at a glance{{ $teacher->section ? ' / '.$teacher->section : '' }}.</p>
                    </div>
                    <a class="teacher-primary-action" href="{{ route('assessments.create') }}"><span class="material-symbols-outlined" aria-hidden="true">add</span>Create assessment</a>
                </header>

                <section class="teacher-metrics" aria-label="Class performance">
                    @foreach ([
                        ['label' => 'Average Accuracy', 'value' => $dashboardMetrics['average_accuracy'] === null ? 'No Data' : $dashboardMetrics['average_accuracy'].'%', 'note' => 'Completed assessments', 'icon' => 'trending_up'],
                        ['label' => 'Students With Results', 'value' => number_format($dashboardMetrics['students_with_results']), 'note' => $students->count().' student accounts', 'icon' => 'group'],
                        ['label' => 'Needs Attention', 'value' => number_format($dashboardMetrics['needs_attention']), 'note' => 'Average accuracy below 75%', 'icon' => 'flag'],
                        ['label' => 'Total Points', 'value' => number_format($dashboardMetrics['total_points']), 'note' => 'Earned by your students', 'icon' => 'stars'],
                    ] as $metric)
                        <div class="teacher-metric">
                            <div class="teacher-metric-label"><span>{{ $metric['label'] }}</span><span class="material-symbols-outlined" aria-hidden="true">{{ $metric['icon'] }}</span></div>
                            <strong>{{ $metric['value'] }}</strong>
                            <small>{{ $metric['note'] }}</small>
                        </div>
                    @endforeach
                </section>

                <div class="teacher-overview-grid">
                    <section class="teacher-section">
                        <div class="teacher-section-heading">
                            <div><h2>Student Performance Overview</h2><p>Latest assessment submissions</p></div>
                            <a href="{{ route('reports.index') }}">View reports<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a>
                        </div>
                        @if ($recentSubmissions->isEmpty())
                            <div class="teacher-empty">
                                <span class="material-symbols-outlined" aria-hidden="true">monitoring</span>
                                <h3>No student progress yet</h3>
                                <p>No assessments have been completed yet.</p>
                            </div>
                        @else
                            <div class="teacher-results">
                                @foreach ($recentSubmissions as $submission)
                                    @php
                                        $accuracy = $submission->scorePercentage();
                                        $assessment = $submission->assessment;
                                        $student = $submission->student;
                                        $reviewUrl = $student && $assessment
                                            ? ($assessment->subject === 'literacy'
                                                ? route('teacher.phil-iri.show', $submission)
                                                : route('reports.student', ['student' => $student]).'#submission-'.$submission->id)
                                            : route('reports.index');
                                    @endphp
                                    <a class="teacher-result" href="{{ $reviewUrl }}" aria-label="Review {{ $student?->name ?? 'student' }} assessment submission">
                                        <span class="material-symbols-outlined" aria-hidden="true">task_alt</span>
                                        <div>
                                            <strong>{{ $student?->name ?? 'Student' }}</strong>
                                            <p>{{ $assessment?->title ?? 'Assessment' }}</p>
                                        </div>
                                        <span>{{ $accuracy === null ? 'Pending' : $accuracy.'%' }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </section>
                    <section class="teacher-focus">
                        <div class="teacher-section-heading"><div><h2>Focus Areas</h2><p>Average accuracy by subject</p></div><span class="material-symbols-outlined" aria-hidden="true">target</span></div>
                        @foreach ($focusAreas as $area)
                            <div class="teacher-focus-row">
                                <div><span>{{ $area['label'] }}</span><strong>{{ $area['accuracy'] === null ? 'No Data' : $area['accuracy'].'%' }}</strong></div>
                                <div class="teacher-focus-track" @if ($area['accuracy'] !== null) role="meter" aria-label="{{ $area['label'] }} accuracy" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $area['accuracy'] }}" @endif><span style="width: {{ $area['accuracy'] ?? 0 }}%"></span></div>
                            </div>
                        @endforeach
                        <a href="{{ route('reports.index') }}"><span class="material-symbols-outlined" aria-hidden="true">analytics</span>Class performance report</a>
                    </section>
                </div>

                @php
                    $mlInsights = $recentSubmissions->filter(fn ($submission) => $submission->mlPrediction)->take(4);
                @endphp
                <section class="teacher-section">
                    <div class="teacher-section-heading">
                        <div>
                            <h2>AI Learning Insights</h2>
                            <p>Reading classification predictions for recent completed assessments</p>
                        </div>
                        <span class="material-symbols-outlined" aria-hidden="true">psychology</span>
                    </div>
                    <p class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">
                        AI prediction is a support tool. Teachers should validate results before making educational decisions.
                    </p>
                    @if ($mlInsights->isEmpty())
                        <div class="teacher-empty">
                            <span class="material-symbols-outlined" aria-hidden="true">model_training</span>
                            <h3>No ML predictions yet</h3>
                            <p>Predictions will appear after the local ML API is enabled and students complete assessments.</p>
                        </div>
                    @else
                        <div class="teacher-results">
                            @foreach ($mlInsights as $submission)
                                @php
                                    $prediction = $submission->mlPrediction;
                                    $philResult = \App\Support\PhilIri::forSubmission($submission);
                                    $confidence = $prediction->confidence_score !== null ? round($prediction->confidence_score * 100) : null;
                                @endphp
                                <a class="teacher-result" href="{{ route('reports.student', ['student' => $submission->student]).'#submission-'.$submission->id }}" aria-label="Open AI learning insight for anonymized student {{ $submission->user_id }}">
                                    <span class="material-symbols-outlined" aria-hidden="true">insights</span>
                                    <div>
                                        <strong>Student_{{ str_pad((string) $submission->user_id, 3, '0', STR_PAD_LEFT) }}</strong>
                                        <p>Current Phil-IRI Result: {{ $philResult['label'] ?? 'Not available' }}</p>
                                        <p>ML Prediction: {{ $prediction->prediction }}{{ $confidence !== null ? ' | Confidence: '.$confidence.'%' : '' }}</p>
                                        <p>Recommendation: {{ $prediction->recommendation }}</p>
                                    </div>
                                    <span>{{ $prediction->prediction }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>

                <nav class="teacher-quicklinks" aria-label="Classroom actions">
                    <a href="{{ route('assessments.index') }}"><span class="material-symbols-outlined" aria-hidden="true">assignment</span>Assessments ({{ $assessmentCount }})</a>
                    <a href="{{ route('students.index') }}#add-student"><span class="material-symbols-outlined" aria-hidden="true">person_add</span>Add student</a>
                    <a href="{{ route('profile.edit') }}"><span class="material-symbols-outlined" aria-hidden="true">tune</span>Account settings</a>
                </nav>
            </div>
        </main>
    </div>
</x-app-layout>
