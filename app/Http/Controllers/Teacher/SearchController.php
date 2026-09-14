<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $teacher = $request->user();
        abort_unless($teacher?->isTeacher(), 403);

        $query = trim((string) $request->query('q', ''));
        $like = '%'.$query.'%';

        $students = collect();
        $assessments = collect();
        $submissions = collect();

        if ($query !== '') {
            $students = User::query()
                ->where('role', 'student')
                ->where(function ($builder) use ($like): void {
                    $builder->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('section', 'like', $like);
                })
                ->orderBy('name')
                ->limit(8)
                ->get();

            $assessments = Assessment::query()
                ->where('created_by', $teacher->id)
                ->where(function ($builder) use ($like): void {
                    $builder->where('title', 'like', $like)
                        ->orWhere('subject', 'like', $like)
                        ->orWhere('quiz_type', 'like', $like)
                        ->orWhere('assessment_type', 'like', $like)
                        ->orWhere('status', 'like', $like)
                        ->orWhere('story_title', 'like', $like);
                })
                ->latest()
                ->limit(8)
                ->get();

            $submissions = AssessmentSubmission::query()
                ->with(['student', 'assessment'])
                ->whereHas('assessment', fn ($builder) => $builder->where('created_by', $teacher->id))
                ->where(function ($builder) use ($like): void {
                    $builder->whereHas('student', function ($studentQuery) use ($like): void {
                        $studentQuery->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    })->orWhereHas('assessment', function ($assessmentQuery) use ($like): void {
                        $assessmentQuery->where('title', 'like', $like)
                            ->orWhere('subject', 'like', $like)
                            ->orWhere('quiz_type', 'like', $like);
                    });
                })
                ->latest('submitted_at')
                ->limit(8)
                ->get();
        }

        $quickLinks = collect([
            ['label' => 'Dashboard', 'description' => 'Teacher overview and recent activity', 'href' => route('teacher.dashboard'), 'keywords' => ['dashboard', 'home', 'overview']],
            ['label' => 'Students', 'description' => 'Roster and add student form', 'href' => route('students.index'), 'keywords' => ['student', 'students', 'roster', 'add student', 'enroll']],
            ['label' => 'Assessments', 'description' => 'Create, publish, lock, and delete assessments', 'href' => route('assessments.index'), 'keywords' => ['assessment', 'assessments', 'quiz', 'question', 'create', 'publish', 'lock']],
            ['label' => 'Reports', 'description' => 'Saved assessment outputs and progress', 'href' => route('reports.index'), 'keywords' => ['report', 'reports', 'score', 'scores', 'output', 'results']],
            ['label' => 'Profile', 'description' => 'Teacher account settings', 'href' => route('profile.edit'), 'keywords' => ['profile', 'account', 'settings']],
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

        return view('teacher.search', [
            'query' => $query,
            'quickLinks' => $quickLinks,
            'students' => $students,
            'assessments' => $assessments,
            'submissions' => $submissions,
        ]);
    }
}