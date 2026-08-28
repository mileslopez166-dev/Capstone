<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TokenRequestController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AssessmentController;
use App\Models\User;
use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Http\Controllers\ProfileController;
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

Route::get('/admin/users/{user}/edit', [UserManagementController::class, 'edit'])
    ->middleware(['auth', 'verified'])
    ->name('admin.users.edit');

Route::patch('/admin/users/{user}', [UserManagementController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('admin.users.update');

Route::delete('/admin/users/{user}', [UserManagementController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('admin.users.destroy');

Route::get('/admin/users/trash', [UserManagementController::class, 'trash'])
    ->middleware(['auth', 'verified'])
    ->name('admin.users.trash');

Route::delete('/admin/users/trash', [UserManagementController::class, 'emptyTrash'])
    ->middleware(['auth', 'verified'])
    ->name('admin.users.trash.empty');

Route::post('/admin/users/{id}/restore', [UserManagementController::class, 'restore'])
    ->middleware(['auth', 'verified'])
    ->name('admin.users.restore');

Route::delete('/admin/users/{id}/force-delete', [UserManagementController::class, 'forceDelete'])
    ->middleware(['auth', 'verified'])
    ->name('admin.users.force-delete');

Route::get('/student/dashboard', function () use ($visibleAssessmentsForStudent, $submissionAccuracy) {
    $student = auth()->user();
    abort_unless($student?->isStudent(), 403);

    $pendingAssessments = $visibleAssessmentsForStudent($student)
        ->whereDoesntHave('submissions', fn ($query) => $query->where('user_id', $student->id))
        ->latest()
        ->get();

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

Route::get('/student/activities', function () use ($visibleAssessmentsForStudent) {
    $student = auth()->user();
    abort_unless($student?->isStudent(), 403);

    $pendingAssessments = $visibleAssessmentsForStudent($student)
        ->whereDoesntHave('submissions', fn ($query) => $query->where('user_id', $student->id))
        ->latest()
        ->get();

    return view('student.activities', [
        'pendingAssessments' => $pendingAssessments,
    ]);
})->middleware(['auth', 'verified'])->name('student.activities');

Route::get('/student/assessments/{assessment}', function (Assessment $assessment) use ($studentTargetSection) {
    $student = auth()->user();
    abort_unless($student?->isStudent(), 403);
    abort_unless($assessment->status === 'published', 404);

    $targetSection = $studentTargetSection($student);
    abort_unless(in_array($assessment->target_section, ['all', null], true) || ($targetSection && $assessment->target_section === $targetSection), 404);

    return view('student.assessment', [
        'assessment' => $assessment,
    ]);
})->middleware(['auth', 'verified'])->name('student.assessments.show');
Route::post('/student/assessments/{assessment}/submit', [AssessmentController::class, 'submitStudentAttempt'])
    ->middleware(['auth', 'verified'])
    ->name('student.assessments.submit');


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

Route::get('/teacher/students', function () use ($submissionAccuracy) {
    $teacher = auth()->user();
    abort_unless($teacher?->isTeacher(), 403);

    $teacherSubmissions = AssessmentSubmission::query()
        ->with('assessment')
        ->whereHas('assessment', fn ($query) => $query->where('created_by', $teacher->id))
        ->get();

    $students = User::query()
        ->where('role', 'student')
        ->orderBy('name')
        ->get()
        ->map(function (User $student) use ($teacherSubmissions, $submissionAccuracy): User {
            $studentSubmissions = $teacherSubmissions->where('user_id', $student->id);
            $student->completed_assessments_count = $studentSubmissions->count();
            $student->average_accuracy = $studentSubmissions->isNotEmpty()
                ? (int) round($studentSubmissions->avg(fn ($submission) => $submissionAccuracy($submission)))
                : null;

            return $student;
        });

    return view('students.index', [
        'students' => $students,
    ]);
})->middleware(['auth', 'verified'])->name('students.index');

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
    ]);
})->middleware(['auth', 'verified'])->name('students.show');

Route::get('/teacher/reports', function () use ($submissionAccuracy) {
    $teacher = auth()->user();
    abort_unless($teacher?->isTeacher(), 403);

    $submissions = AssessmentSubmission::query()
        ->with(['student', 'assessment'])
        ->whereHas('assessment', fn ($query) => $query->where('created_by', $teacher->id))
        ->latest('submitted_at')
        ->get();

    $students = User::query()
        ->where('role', 'student')
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

    $student ??= User::query()->where('role', 'student')->orderBy('name')->first();
    abort_unless($student && $student->isStudent(), 404);

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
