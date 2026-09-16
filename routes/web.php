<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\TokenRequestController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssessmentRetakeRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\SearchController as StudentSearchController;
use App\Http\Controllers\Teacher\SearchController as TeacherSearchController;
use App\Http\Controllers\Teacher\StudentController;
use App\Models\Assessment;
use App\Models\AssessmentRetakeRequest;
use App\Models\AssessmentSubmission;
use App\Models\User;
use App\Support\StudentLeaderboard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->dashboardRouteName());
    }

    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->dashboardRouteName());
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/notifications/mark-read', [NotificationController::class, 'markRead'])
    ->middleware(['auth', 'verified'])
    ->name('notifications.mark-read');

Route::view('/support', 'support.developing')
    ->middleware(['auth', 'verified'])
    ->name('support.developing');

$studentTargetSection = function (User $student): ?string {
    return $student->section ? strtolower(str_replace(' ', '_', $student->section)) : null;
};

$visibleAssessmentsForStudent = function (User $student) use ($studentTargetSection) {
    $targetSection = $studentTargetSection($student);

    return Assessment::query()
        ->where('status', 'published')
        ->where(function ($query) use ($targetSection): void {
            $query->where('target_section', 'all')
                ->orWhereNull('target_section');

            if ($targetSection) {
                $query->orWhere('target_section', $targetSection);
            }
        });
};

$submissionAccuracy = function (AssessmentSubmission $submission): int {
    return $submission->question_count > 0
        ? (int) round(($submission->correct_count / $submission->question_count) * 100)
        : 0;
};
Route::get('/teacher/dashboard', function () use ($submissionAccuracy) {
    $teacher = auth()->user();
    abort_unless($teacher?->isTeacher(), 403);

    $students = User::query()
        ->where('role', 'student')
        ->orderBy('name')
        ->get();

    $assessmentCount = Assessment::query()
        ->where('created_by', $teacher->id)
        ->count();

    $submissions = AssessmentSubmission::query()
        ->with(['student', 'assessment'])
        ->whereHas('assessment', fn ($query) => $query->where('created_by', $teacher->id))
        ->latest('submitted_at')
        ->get();

    $students = $students->map(function (User $student) use ($submissions, $submissionAccuracy): User {
        $studentSubmissions = $submissions->where('user_id', $student->id);
        $student->completed_assessments_count = $studentSubmissions->count();
        $student->average_accuracy = $studentSubmissions->isNotEmpty()
            ? (int) round($studentSubmissions->avg(fn ($submission) => $submissionAccuracy($submission)))
            : null;
        $student->total_points = (int) $studentSubmissions->sum('points');

        return $student;
    });

    $dashboardMetrics = [
        'average_accuracy' => $submissions->isNotEmpty() ? (int) round($submissions->avg(fn ($submission) => $submissionAccuracy($submission))) : null,
        'students_with_results' => $submissions->pluck('user_id')->unique()->count(),
        'needs_attention' => $students->filter(fn (User $student): bool => $student->average_accuracy !== null && $student->average_accuracy < 75)->count(),
        'total_points' => (int) $submissions->sum('points'),
    ];

    $focusAreas = collect(['literacy' => 'Literacy', 'numeracy' => 'Numeracy'])
        ->map(function (string $label, string $subject) use ($submissions, $submissionAccuracy): array {
            $subjectSubmissions = $submissions->filter(fn ($submission): bool => ($submission->assessment?->subject ?? null) === $subject);

            return [
                'label' => $label,
                'accuracy' => $subjectSubmissions->isNotEmpty() ? (int) round($subjectSubmissions->avg(fn ($submission) => $submissionAccuracy($submission))) : null,
            ];
        })
        ->values();

    return view('dashboard', [
        'students' => $students,
        'assessmentCount' => $assessmentCount,
        'dashboardMetrics' => $dashboardMetrics,
        'focusAreas' => $focusAreas,
        'recentSubmissions' => $submissions->take(5),
    ]);
})->middleware(['auth', 'verified'])->name('teacher.dashboard');

Route::get('/admin/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('admin.dashboard');

Route::get('/admin/search', SearchController::class)
    ->middleware(['auth', 'verified'])
    ->name('admin.search');

Route::get('/admin/token-requests', [TokenRequestController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('admin.token-requests.index');

Route::post('/admin/token-requests/{user}/approve', [TokenRequestController::class, 'approve'])
    ->middleware(['auth', 'verified'])
    ->name('admin.token-requests.approve');

Route::post('/admin/token-requests/{user}/decline', [TokenRequestController::class, 'decline'])
    ->middleware(['auth', 'verified'])
    ->name('admin.token-requests.decline');

Route::post('/admin/users', [UserManagementController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('admin.users.store');

Route::get('/admin/users/trash', [UserManagementController::class, 'trash'])
    ->middleware(['auth', 'verified'])
    ->name('admin.users.trash');

Route::delete('/admin/users/trash', [UserManagementController::class, 'emptyTrash'])
    ->middleware(['auth', 'verified'])
    ->name('admin.users.trash.empty');

Route::get('/admin/users/{user}/edit', [UserManagementController::class, 'edit'])
    ->middleware(['auth', 'verified'])
    ->name('admin.users.edit');

Route::patch('/admin/users/{user}', [UserManagementController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('admin.users.update');

Route::delete('/admin/users/{user}', [UserManagementController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('admin.users.destroy');

Route::post('/admin/users/{id}/restore', [UserManagementController::class, 'restore'])
    ->middleware(['auth', 'verified'])
    ->name('admin.users.restore');

Route::delete('/admin/users/{id}/force-delete', [UserManagementController::class, 'forceDelete'])
    ->middleware(['auth', 'verified'])
    ->name('admin.users.force-delete');

Route::get('/student/search', StudentSearchController::class)
    ->middleware(['auth', 'verified'])
    ->name('student.search');

Route::get('/student/dashboard', function () use ($visibleAssessmentsForStudent, $submissionAccuracy) {
    $student = auth()->user();
    abort_unless($student?->isStudent(), 403);

    $retakeAllowances = AssessmentRetakeRequest::query()
        ->where('user_id', $student->id)
        ->where('status', 'approved')
        ->where('remaining_tries', '>', 0)
        ->selectRaw('assessment_id, SUM(remaining_tries) as remaining_tries')->groupBy('assessment_id')
        ->pluck('remaining_tries', 'assessment_id');

    $pendingAssessments = $visibleAssessmentsForStudent($student)
        ->withCount(['submissions as student_attempts_count' => fn ($query) => $query->where('user_id', $student->id)])
        ->latest()
        ->get()
        ->filter(fn (Assessment $assessment): bool => $assessment->remainingIncludedAttempts($assessment->student_attempts_count) > 0 || ($retakeAllowances[$assessment->id] ?? 0) > 0)
        ->values();

    $submissions = AssessmentSubmission::query()
        ->with('assessment')
        ->where('user_id', $student->id)
        ->latest('submitted_at')
        ->get();

    $studentMetrics = [
        'pending_count' => $pendingAssessments->count(),
        'completed_count' => $submissions->count(),
        'average_accuracy' => $submissions->isNotEmpty() ? (int) round($submissions->avg(fn ($submission) => $submissionAccuracy($submission))) : null,
        'total_points' => (int) $submissions->sum('points'),
    ];

    return view('student.dashboard', [
        'pendingAssessments' => $pendingAssessments,
        'recentSubmissions' => $submissions->take(3),
        'studentMetrics' => $studentMetrics,
    ]);
})->middleware(['auth', 'verified'])->name('student.dashboard');

Route::get('/student/activities', function () use ($visibleAssessmentsForStudent, $submissionAccuracy) {
    $student = auth()->user();
    abort_unless($student?->isStudent(), 403);

    $retakeAllowances = AssessmentRetakeRequest::query()
        ->where('user_id', $student->id)
        ->where('status', 'approved')
        ->where('remaining_tries', '>', 0)
        ->selectRaw('assessment_id, SUM(remaining_tries) as remaining_tries')->groupBy('assessment_id')
        ->pluck('remaining_tries', 'assessment_id');

    $latestRequests = AssessmentRetakeRequest::query()
        ->where('user_id', $student->id)
        ->latest()
        ->get()
        ->unique('assessment_id')
        ->keyBy('assessment_id');

    $visibleAssessments = $visibleAssessmentsForStudent($student)
        ->withCount(['submissions as student_attempts_count' => fn ($query) => $query->where('user_id', $student->id)])
        ->latest()
        ->get();

    $pendingAssessments = $visibleAssessments
        ->filter(fn (Assessment $assessment): bool => $assessment->remainingIncludedAttempts($assessment->student_attempts_count) > 0 || ($retakeAllowances[$assessment->id] ?? 0) > 0)
        ->values();

    $completedSubmissions = AssessmentSubmission::query()
        ->with('assessment')
        ->where('user_id', $student->id)
        ->latest('submitted_at')
        ->get()
        ->groupBy('assessment_id')
        ->map(function ($attempts) use ($submissionAccuracy, $retakeAllowances, $latestRequests) {
            $latestAttempt = $attempts->first();
            $latestAttempt->attempts_count = $attempts->count();
            $latestAttempt->accuracy = $submissionAccuracy($latestAttempt);
            $includedRemaining = $latestAttempt->assessment?->remainingIncludedAttempts($attempts->count()) ?? 0;
            $latestAttempt->remaining_retake_tries = $includedRemaining === PHP_INT_MAX
                ? PHP_INT_MAX
                : $includedRemaining + (int) ($retakeAllowances[$latestAttempt->assessment_id] ?? 0);
            $latestAttempt->latest_retake_request = $latestRequests[$latestAttempt->assessment_id] ?? null;
            $answers = collect($latestAttempt->answers ?? []);
            $latestAttempt->review_items = collect($latestAttempt->assessment?->manual_questions ?? [])
                ->values()
                ->map(function (array $question, int $index) use ($answers): array {
                    $selectedLetter = (string) ($answers->get($index) ?? $answers->get((string) $index) ?? '');
                    $correctLetter = (string) ($question['correct_answer'] ?? '');
                    $options = $question['answers'] ?? [];

                    return [
                        'number' => $index + 1,
                        'question' => $question['question'] ?? 'Question',
                        'selected_letter' => $selectedLetter,
                        'selected_text' => $selectedLetter !== '' ? ($options[$selectedLetter] ?? 'Answer '.$selectedLetter) : 'No answer',
                        'correct_letter' => $correctLetter,
                        'correct_text' => $correctLetter !== '' ? ($options[$correctLetter] ?? 'Answer '.$correctLetter) : 'No correct answer set',
                        'is_correct' => $selectedLetter !== '' && $selectedLetter === $correctLetter,
                    ];
                });
            $latestAttempt->wrong_review_items = $latestAttempt->review_items
                ->filter(fn (array $item): bool => ! $item['is_correct'])
                ->values();

            return $latestAttempt;
        })
        ->values();

    return view('student.activities', [
        'pendingAssessments' => $pendingAssessments,
        'completedSubmissions' => $completedSubmissions,
    ]);
})->middleware(['auth', 'verified'])->name('student.activities');

Route::get('/student/assessments/{assessment}', [AssessmentController::class, 'showStudent'])
    ->middleware(['auth', 'verified'])->name('student.assessments.show');
Route::post('/student/assessments/{assessment}/progress', [AssessmentController::class, 'saveStudentProgress'])
    ->middleware(['auth', 'verified'])->name('student.assessments.progress');
Route::post('/student/assessments/{assessment}/submit', [AssessmentController::class, 'submitStudentAttempt'])
    ->middleware(['auth', 'verified'])
    ->name('student.assessments.submit');
Route::post('/student/assessments/{assessment}/retake-request', [AssessmentRetakeRequestController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('student.assessments.retake-request');

Route::get('/student/leaderboard/{scope?}', function (?string $scope = 'all') {
    $student = auth()->user();
    abort_unless($student?->isStudent(), 403);

    $scopes = [
        'all' => 'All Sections',
        'section_a' => 'Section A',
        'section_b' => 'Section B',
        'section_c' => 'Section C',
    ];

    abort_unless(array_key_exists($scope, $scopes), 404);

    $leaderboard = StudentLeaderboard::entries($scope === 'all' ? null : $scopes[$scope]);

    $currentStudentRank = $leaderboard->firstWhere('student.id', $student->id)['rank'] ?? null;

    return view('student.leaderboard', [
        'scope' => $scope,
        'scopeLabel' => $scopes[$scope],
        'leaderboard' => $leaderboard,
        'currentStudentRank' => $currentStudentRank,
    ]);
})->middleware(['auth', 'verified'])->name('student.leaderboard');

Route::get('/student/rewards', function () use ($submissionAccuracy) {
    $student = auth()->user();
    abort_unless($student?->isStudent(), 403);

    $submissions = AssessmentSubmission::query()
        ->with('assessment')
        ->where('user_id', $student->id)
        ->latest('submitted_at')
        ->get();

    return view('student.rewards', [
        'recentSubmissions' => $submissions->take(5),
        'studentMetrics' => [
            'completed_count' => $submissions->count(),
            'saved_results' => $submissions->count(),
            'average_accuracy' => $submissions->isNotEmpty() ? (int) round($submissions->avg(fn ($submission) => $submissionAccuracy($submission))) : null,
            'total_points' => (int) $submissions->sum('points'),
            'rewards_available' => $submissions->filter(fn ($submission) => $submissionAccuracy($submission) >= 75)->count(),
        ],
    ]);
})->middleware(['auth', 'verified'])->name('student.rewards');

Route::get('/teacher/search', TeacherSearchController::class)
    ->middleware(['auth', 'verified'])
    ->name('teacher.search');

Route::get('/teacher/students', function () use ($submissionAccuracy) {
    $teacher = auth()->user();
    abort_unless($teacher?->isTeacher(), 403);

    $teacherSubmissions = AssessmentSubmission::query()
        ->with('assessment')
        ->whereHas('assessment', fn ($query) => $query->where('created_by', $teacher->id))
        ->get();

    $activeStudentIds = DB::table('sessions')
        ->whereNotNull('user_id')
        ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime', 120))->timestamp)
        ->pluck('user_id')
        ->map(fn ($id): int => (int) $id)
        ->all();

    $students = User::query()
        ->where('role', 'student')
        ->orderBy('name')
        ->get()
        ->map(function (User $student) use ($teacherSubmissions, $submissionAccuracy, $activeStudentIds): User {
            $studentSubmissions = $teacherSubmissions->where('user_id', $student->id);
            $student->completed_assessments_count = $studentSubmissions->count();
            $student->average_accuracy = $studentSubmissions->isNotEmpty()
                ? (int) round($studentSubmissions->avg(fn ($submission) => $submissionAccuracy($submission)))
                : null;
            $student->is_online = in_array($student->id, $activeStudentIds, true);

            return $student;
        });

    return view('students.index', [
        'students' => $students,
    ]);
})->middleware(['auth', 'verified'])->name('students.index');

Route::post('/teacher/students', [StudentController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('students.store');

Route::get('/teacher/students/{student}', function (User $student) use ($submissionAccuracy) {
    $teacher = auth()->user();
    abort_unless($teacher?->isTeacher(), 403);
    abort_unless($student->isStudent(), 404);

    $submissions = AssessmentSubmission::query()
        ->with('assessment')
        ->where('user_id', $student->id)
        ->whereHas('assessment', fn ($query) => $query->where('created_by', $teacher->id))
        ->latest('submitted_at')
        ->get();

    $studentMetrics = [
        'completed_count' => $submissions->count(),
        'average_accuracy' => $submissions->isNotEmpty() ? (int) round($submissions->avg(fn ($submission) => $submissionAccuracy($submission))) : null,
        'total_points' => (int) $submissions->sum('points'),
    ];

    $studentIsOnline = DB::table('sessions')
        ->where('user_id', $student->id)
        ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime', 120))->timestamp)
        ->exists();

    $assessmentRequests = AssessmentRetakeRequest::query()
        ->with('assessment')
        ->where('user_id', $student->id)
        ->whereHas('assessment', fn ($query) => $query->where('created_by', $teacher->id))
        ->latest()
        ->get();

    $subjectBreakdown = collect(['literacy' => 'Literacy', 'numeracy' => 'Numeracy'])
        ->map(function (string $label, string $subject) use ($submissions, $submissionAccuracy): array {
            $subjectSubmissions = $submissions->filter(fn ($submission): bool => ($submission->assessment?->subject ?? null) === $subject);

            return [
                'label' => $label,
                'accuracy' => $subjectSubmissions->isNotEmpty() ? (int) round($subjectSubmissions->avg(fn ($submission) => $submissionAccuracy($submission))) : null,
                'count' => $subjectSubmissions->count(),
            ];
        })
        ->values();

    return view('students.show', [
        'student' => $student,
        'submissions' => $submissions,
        'studentMetrics' => $studentMetrics,
        'subjectBreakdown' => $subjectBreakdown,
        'studentIsOnline' => $studentIsOnline,
        'assessmentRequests' => $assessmentRequests,
    ]);
})->middleware(['auth', 'verified'])->name('students.show');
Route::post('/teacher/students/{student}/assessment-requests/{retakeRequest}/approve', [AssessmentRetakeRequestController::class, 'approve'])
    ->middleware(['auth', 'verified'])
    ->name('students.assessment-requests.approve');

Route::post('/teacher/students/{student}/assessment-requests/{retakeRequest}/decline', [AssessmentRetakeRequestController::class, 'decline'])
    ->middleware(['auth', 'verified'])
    ->name('students.assessment-requests.decline');

Route::get('/teacher/reports', function () use ($submissionAccuracy) {
    $teacher = auth()->user();
    abort_unless($teacher?->isTeacher(), 403);

    $submissions = AssessmentSubmission::query()
        ->with(['student', 'assessment'])
        ->whereHas('assessment', fn ($query) => $query->where('created_by', $teacher->id))
        ->latest('submitted_at')
        ->get();

    $studentIdsWithTeacherResults = $submissions->pluck('user_id')->unique()->values();

    $students = User::query()
        ->where('role', 'student')
        ->whereIn('id', $studentIdsWithTeacherResults)
        ->orderBy('name')
        ->get()
        ->map(function (User $student) use ($submissions, $submissionAccuracy): User {
            $studentSubmissions = $submissions->where('user_id', $student->id);
            $student->completed_assessments_count = $studentSubmissions->count();
            $student->average_accuracy = $studentSubmissions->isNotEmpty()
                ? (int) round($studentSubmissions->avg(fn ($submission) => $submissionAccuracy($submission)))
                : null;
            $student->total_points = (int) $studentSubmissions->sum('points');

            return $student;
        });

    $reportMetrics = [
        'average_accuracy' => $submissions->isNotEmpty() ? (int) round($submissions->avg(fn ($submission) => $submissionAccuracy($submission))) : null,
        'completed_count' => $submissions->count(),
        'students_with_results' => $submissions->pluck('user_id')->unique()->count(),
    ];

    $subjectBreakdown = collect(['literacy' => 'Literacy', 'numeracy' => 'Numeracy'])
        ->map(function (string $label, string $subject) use ($submissions, $submissionAccuracy): array {
            $subjectSubmissions = $submissions->filter(fn ($submission): bool => ($submission->assessment?->subject ?? null) === $subject);

            return [
                'label' => $label,
                'accuracy' => $subjectSubmissions->isNotEmpty() ? (int) round($subjectSubmissions->avg(fn ($submission) => $submissionAccuracy($submission))) : null,
                'count' => $subjectSubmissions->count(),
            ];
        })
        ->values();

    return view('reports.index', [
        'students' => $students,
        'submissions' => $submissions,
        'reportMetrics' => $reportMetrics,
        'subjectBreakdown' => $subjectBreakdown,
    ]);
})->middleware(['auth', 'verified'])->name('reports.index');

Route::get('/teacher/reports/students/{student?}', function (?User $student = null) use ($submissionAccuracy) {
    $teacher = auth()->user();
    abort_unless($teacher?->isTeacher(), 403);

    $reportableStudents = User::query()
        ->where('role', 'student')
        ->whereHas('assessmentSubmissions.assessment', fn ($query) => $query->where('created_by', $teacher->id));

    if ($student === null) {
        $student = (clone $reportableStudents)->orderBy('name')->first();

        if (! $student) {
            return redirect()
                ->route('reports.index')
                ->with('status', 'No answered assessment reports are available yet.');
        }
    } else {
        abort_unless($student->isStudent(), 404);
        abort_unless((clone $reportableStudents)->whereKey($student->id)->exists(), 404);
    }

    $submissions = AssessmentSubmission::query()
        ->with('assessment')
        ->where('user_id', $student->id)
        ->whereHas('assessment', fn ($query) => $query->where('created_by', $teacher->id))
        ->latest('submitted_at')
        ->get();

    $studentMetrics = [
        'completed_count' => $submissions->count(),
        'average_accuracy' => $submissions->isNotEmpty() ? (int) round($submissions->avg(fn ($submission) => $submissionAccuracy($submission))) : null,
        'total_points' => (int) $submissions->sum('points'),
    ];

    $subjectBreakdown = collect(['literacy' => 'Literacy', 'numeracy' => 'Numeracy'])
        ->map(function (string $label, string $subject) use ($submissions, $submissionAccuracy): array {
            $subjectSubmissions = $submissions->filter(fn ($submission): bool => ($submission->assessment?->subject ?? null) === $subject);

            return [
                'label' => $label,
                'accuracy' => $subjectSubmissions->isNotEmpty() ? (int) round($subjectSubmissions->avg(fn ($submission) => $submissionAccuracy($submission))) : null,
                'count' => $subjectSubmissions->count(),
            ];
        })
        ->values();

    return view('reports.student', [
        'student' => $student,
        'submissions' => $submissions,
        'studentMetrics' => $studentMetrics,
        'subjectBreakdown' => $subjectBreakdown,
    ]);
})->middleware(['auth', 'verified'])->name('reports.student');

Route::get('/teacher/assessments', [AssessmentController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('assessments.index');

Route::post('/teacher/assessments', [AssessmentController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('assessments.store');

Route::patch('/teacher/assessments/{assessment}/availability', [AssessmentController::class, 'updateAvailability'])
    ->middleware(['auth', 'verified'])
    ->name('assessments.availability');
Route::patch('/teacher/assessments/{assessment}/retries', [AssessmentController::class, 'updateRetries'])
    ->middleware(['auth', 'verified'])->name('assessments.retries');

Route::get('/teacher/assessments/{assessment}', [AssessmentController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('assessments.show');

Route::delete('/teacher/assessments/{assessment}', [AssessmentController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('assessments.destroy');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
