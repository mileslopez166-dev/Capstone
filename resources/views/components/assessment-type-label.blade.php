@props(['assessment'])
@if ($assessment)
    @php
        $type = $assessment->subject === 'numeracy' ? 'numeracy' : ($assessment->assessment_type ?? 'silent_reading');
        [$label, $icon] = match ($type) {
            'oral_reading' => ['Oral Reading Assessment', 'record_voice_over'],
            'silent_reading' => ['Silent Reading Assessment', 'menu_book'],
            'listening_comprehension' => ['Listening Comprehension Assessment', 'hearing'],
            'group_screening' => ['Group Screening Test', 'groups'],
            'numeracy' => $assessment->worksheet_number ? ['Numeracy Worksheet '.$assessment->worksheet_number, 'menu_book'] : ['Numeracy Assessment', 'calculate'],
            default => ['Literacy Assessment', 'assignment'],
        };
    @endphp
    <p class="mt-2 flex items-start gap-2 text-sm font-bold text-primary" data-assessment-type="{{ $type }}">
        <span class="material-symbols-outlined shrink-0 text-lg" aria-hidden="true">{{ $icon }}</span>
        <span class="min-w-0 break-words">{{ $label }}</span>
    </p>
@endif
