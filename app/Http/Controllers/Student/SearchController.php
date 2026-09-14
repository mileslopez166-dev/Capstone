<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentRetakeRequest;
use App\Models\AssessmentSubmission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $student = $request->user();
        abort_unless($student?->isStudent(), 403);

        $query = trim((string) $request->query('q', ''));
        $like = '%'.$query.'%';
        $targetSection = $student->section ? strtolower(str_replace(' ', '_', $student->section)) : null;

        $assessments = collect();
        $submissions = collect();

        if ($query !== '') {
            $retakeAllowances = AssessmentRetakeRequest::query()
                ->where('user_id', $student->id)
                ->where('status', 'approved')
                ->where('remaining_tries', '>', 0)
                ->pluck('remaining_tries', 'assessment_id');

            $assessments = Assessment::query()
                ->where('status', 'published')
                ->where(function ($builder) use ($targetSection): void {
                    $builder->where('target_section', 'all')
                        ->orWhereNull('target_section');

                    if ($targetSection) {
                        $builder->orWhere('target_section', $targetSection);
                    }
                })
                ->where(function ($builder) use ($like): void {
                    $builder->where('title', 'like', $like)
                        ->orWhere('subject', 'like', $like)
                        ->orWhere('quiz_type', 'like', $like)
                        ->orWhere('assessment_type', 'like', $like)
                        ->orWhere('story_title', 'like', $like)
                        ->orWhere('instructions', 'like', $like);
                })
                ->withCount(['submissions as student_attempts_count' => fn ($builder) => $builder->where('user_id', $student->id)])
                ->latest()
                ->limit(8)
                ->get()
                ->map(function (Assessment $assessment) use ($retakeAllowances): Assessment {
                    $assessment->can_open = $assessment->student_attempts_count === 0 || ($retakeAllowances[$assessment->id] ?? 0) > 0;

                    return $assessment;
                });

            $submissions = AssessmentSubmission::query()
                ->with('assessment')
                ->where('user_id', $student->id)
                ->where(function ($builder) use ($like): void {
                    $builder->whereHas('assessment', function ($assessmentQuery) use ($like): void {
                        $assessmentQuery->where('title', 'like', $like)
                            ->orWhere('subject', 'like', $like)
                            ->orWhere('quiz_type', 'like', $like)
                            ->orWhere('assessment_type', 'like', $like);
                    });
                })
                ->latest('submitted_at')
                ->limit(8)
                ->get();
        }

        $quickLinks = collect([
            ['label' => 'Dashboard', 'description' => 'Student overview and latest assignments', 'href' => route('student.dashboard'), 'keywords' => ['dashboard', 'home', 'overview']],
            ['label' => 'Activities', 'description' => 'Pending assessments and recorded outputs', 'href' => route('student.activities'), 'keywords' => ['activity', 'activities', 'assessment', 'quiz', 'recorded', 'output']],
            ['label' => 'Leaderboard', 'description' => 'All section rankings and points', 'href' => route('student.leaderboard'), 'keywords' => ['leaderboard', 'rank', 'ranking', 'points', 'section']],
            ['label' => 'Rewards', 'description' => 'Saved progress and achievements', 'href' => route('student.rewards'), 'keywords' => ['reward', 'rewards', 'achievement', 'points']],
            ['label' => 'Profile', 'description' => 'Student account and avatar', 'href' => route('profile.edit'), 'keywords' => ['profile', 'avatar', 'account', 'settings']],
            ['label' => 'Support', 'description' => 'System development status', 'href' => route('support.developing'), 'keywords' => ['support', 'help', 'developing']],
        ])->filter(function (array $link) use ($query): bool {
            if ($query === '') {
                return true;
            }

            $needle = strtolower($query);

            return str_contains(strtolower($link['label']), $needle)
                || str_contains(strtolower($link['description']), $needle)
                || collect($link['keywords'])->contains(fn (string $keyword): bool => str_contains($keyword, $needle));
        })->values();

        return view('student.search', [
            'query' => $query,
            'quickLinks' => $quickLinks,
            'assessments' => $assessments,
            'submissions' => $submissions,
        ]);
    }
}