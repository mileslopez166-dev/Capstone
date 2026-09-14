<x-app-layout>
    @php
        $teacher = Auth::user();
        $teacherName = $teacher->name;
        $teacherInitials = str($teacherName)->explode(' ')->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
    @endphp
    <div class="min-h-screen">
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
                    <a class="teacher-primary-action" href="{{ route('assessments.index') }}"><span class="material-symbols-outlined" aria-hidden="true">add</span>Create assessment</a>
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
                                    @php $accuracy = $submission->question_count > 0 ? (int) round(($submission->correct_count / $submission->question_count) * 100) : 0; @endphp
                                    <div class="teacher-result">
                                        <span class="material-symbols-outlined" aria-hidden="true">task_alt</span>
                                        <div>
                                            <strong>{{ $submission->student?->name ?? 'Student' }}</strong>
                                            <p>{{ $submission->assessment?->title ?? 'Assessment' }}</p>
                                        </div>
                                        <span>{{ $accuracy }}%</span>
                                    </div>
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

                <section class="teacher-section">
                    <div class="teacher-section-heading">
                        <div><h2>Student Roster</h2><p>{{ $students->count() }} students &middot; Grade 6</p></div>
                        <a href="{{ route('students.index') }}">Open roster<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a>
                    </div>
                    @if ($students->isEmpty())
                        <div class="teacher-empty"><span class="material-symbols-outlined" aria-hidden="true">group</span><h3>No student accounts yet</h3><p>Your student roster is empty.</p></div>
                    @else
                        <div class="teacher-roster">
                            <table>
                                <thead><tr><th>Student</th><th>Section</th><th>Progress</th><th class="text-right">Profile</th></tr></thead>
                                <tbody>
                                    @foreach ($students->take(8) as $student)
                                        <tr>
                                            <td><a class="roster-name" href="{{ route('students.show', $student) }}">{{ $student->name }}</a><small>{{ $student->email }}</small></td>
                                            <td>{{ $student->section ?: 'Unassigned' }}</td>
                                            <td>{{ $student->average_accuracy === null ? 'No Data Yet' : $student->average_accuracy.'% Avg' }}</td>
                                            <td class="text-right"><a class="inline-flex p-2" href="{{ route('students.show', $student) }}" aria-label="View {{ $student->name }} profile" title="View student profile"><span class="material-symbols-outlined" aria-hidden="true">arrow_outward</span></a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
