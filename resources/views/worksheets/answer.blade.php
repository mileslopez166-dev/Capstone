<x-worksheet-layout :teacher="$assisted" :title="$assessment->title" :back="$assisted ? route('assessments.show', $assessment) : route('worksheets.mission')">
    @if ($assisted)<x-assisted-assessment-banner :student="$student" :assessment="$assessment" />@endif
    @if ($assessment->instructions)<p class="worksheet-instructions">{{ $assessment->instructions }}</p>@endif
    @php
        $config = ['pages' => \App\Support\NumeracyWorksheets::pageConfig($worksheet),
            'state' => $progress->state, 'revision' => $progress->revision, 'attemptKey' => $progress->attempt_key,
            'storageKey' => 'worksheet-'.$student->id.'-'.$progress->attempt_key,
            'saveUrl' => $assisted ? route('teacher.assessments.worksheet.save', [$assessment, $student]) : route('worksheets.save', $assessment),
            'submitUrl' => $assisted ? route('teacher.assessments.worksheet.submit', [$assessment, $student]) : route('worksheets.submit', $assessment),
            'tableUrl' => $assisted ? route('teacher.assessments.worksheet.table', [$assessment, $student]) : route('worksheets.table', $assessment),
            'tableAllowed' => (bool) $progress->multiplication_table_unlocked_at];
    @endphp
    <x-worksheet-reader :worksheet="$worksheet" :config="$config" />
</x-worksheet-layout>
