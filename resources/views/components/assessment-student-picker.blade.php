@props(['assessment', 'students'])
<section class="assisted-picker" aria-labelledby="assisted-picker-title">
    <h2 id="assisted-picker-title">Take with a Student</h2>
    @if ($assessment->status !== 'published')
        <p>This assessment is locked.</p>
    @elseif ($students->isEmpty())
        <p>No approved students in the assigned section.</p>
    @else
        <form method="GET" action="{{ route('teacher.assessments.start', $assessment) }}">
            <label for="assisted-student">Student</label>
            <div class="assisted-picker-controls">
                <select id="assisted-student" name="student_id" required>
                    <option value="">Choose a student</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }} | {{ $student->section ?: 'No section' }} | {{ $student->email }}</option>
                    @endforeach
                </select>
                <button type="submit" class="ui-button"><span class="material-symbols-outlined" aria-hidden="true">play_arrow</span>Start / Resume</button>
            </div>
        </form>
    @endif
</section>
