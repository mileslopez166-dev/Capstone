<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\AssessmentProgress;
use App\Models\User;
use App\Models\WorksheetAttempt;

class WorksheetMission
{
    public static function forStudent(User $student): array
    {
        $section = $student->section ? strtolower(str_replace(' ', '_', $student->section)) : null;
        $assignments = Assessment::with('teacher')->whereNotNull('worksheet_number')->where('status', 'published')
            ->where(fn ($query) => $query->whereIn('target_section', ['all', $section])->orWhereNull('target_section'))
            ->latest()->orderByDesc('id')->get();
        $progress = AssessmentProgress::where('user_id', $student->id)->whereNull('submission_id')
            ->whereIn('assessment_id', $assignments->pluck('id'))->latest('updated_at')->get()
            ->filter(fn ($item) => ! empty($item->state))->keyBy('assessment_id');
        $attempts = WorksheetAttempt::with('assessment')->where('user_id', $student->id)->latest()->orderByDesc('id')->get()
            ->groupBy(fn ($attempt) => $attempt->assessment->worksheet_number);

        $steps = collect(NumeracyWorksheets::all())->map(function ($worksheet) use ($assignments, $progress, $attempts) {
            $number = $worksheet['number'];
            $attempt = $attempts->get($number)?->first();
            $available = $assignments->where('worksheet_number', $number);
            $assignment = $available->first(fn ($item) => $progress->has($item->id)) ?? $available->first();
            $state = $attempt ? ($attempt->reviewed_at ? 'reviewed' : 'submitted')
                : ($assignment ? ($progress->has($assignment->id) ? 'in_progress' : 'ready') : 'locked');

            return [
                'number' => $number, 'parts' => count($worksheet['pages']), 'state' => $state,
                'finished' => $attempt !== null,
                'url' => $attempt ? route('worksheets.review', $attempt) : ($assignment ? route('student.assessments.show', $assignment) : null),
                'image' => ($attempt || $assignment) ? route('worksheets.image', [$number, 1]) : null,
                'label' => match ($state) { 'reviewed' => 'Reviewed', 'submitted' => 'Submitted', 'in_progress' => 'In Progress', 'ready' => 'Ready', default => 'Not Assigned' },
                'action' => $attempt ? 'View Work' : ($state === 'in_progress' ? 'Continue Worksheet' : 'Start Worksheet'),
                'score' => $attempt?->reviewed_at ? $attempt->score.' / '.$attempt->total : null,
            ];
        });
        $next = $steps->firstWhere('state', 'in_progress') ?? $steps->firstWhere('state', 'ready');
        return [
            'steps' => $steps, 'next' => $next, 'total' => $steps->count(),
            'finished' => $steps->where('finished', true)->count(),
            'reviewed' => $steps->where('state', 'reviewed')->count(),
            'ready' => $steps->whereIn('state', ['ready', 'in_progress'])->count(),
            'complete' => $steps->isNotEmpty() && $steps->every('finished', true),
        ];
    }
}
