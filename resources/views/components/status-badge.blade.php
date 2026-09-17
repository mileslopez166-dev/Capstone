@props(['status' => 'not_started'])
@php
    [$label, $icon, $tone] = match ($status) {
        'in_progress' => ['In Progress', 'play_circle', 'blue'],
        'completed' => ['Completed', 'task_alt', 'green'],
        'needs_review' => ['Needs Review', 'rate_review', 'amber'],
        'reviewed' => ['Reviewed', 'verified', 'green'],
        'published' => ['Published', 'check_circle', 'green'],
        'unlocked' => ['Unlocked', 'lock_open', 'green'],
        'draft' => ['Locked', 'lock', 'gray'],
        'cancelled' => ['Cancelled', 'cancel', 'gray'],
        'approved' => ['Approved', 'check_circle', 'green'],
        'pending' => ['Pending', 'schedule', 'amber'],
        'rejected' => ['Declined', 'cancel', 'red'],
        default => ['Not Started', 'radio_button_unchecked', 'gray'],
    };
@endphp
<span {{ $attributes->class(['ui-status', 'ui-status-'.$tone]) }}><span class="material-symbols-outlined" aria-hidden="true">{{ $icon }}</span>{{ $label }}</span>
