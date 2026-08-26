<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TokenRequestController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AssessmentController;
use App\Models\User;
use App\Models\Assessment;
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

Route::get('/teacher/dashboard', function () {
    $students = User::query()
        ->where('role', 'student')
        ->orderBy('name')
        ->get();

    $assessmentCount = Assessment::query()
        ->where('created_by', auth()->id())
        ->count();

    return view('dashboard', [
        'students' => $students,
        'assessmentCount' => $assessmentCount,
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

Route::get('/student/dashboard', function () {
    return view('student.dashboard');
})->middleware(['auth', 'verified'])->name('student.dashboard');

Route::get('/student/activities', function () {
    $pendingAssessments = Assessment::query()
        ->where('status', 'published')
        ->latest()
        ->get();

    return view('student.activities', [
        'pendingAssessments' => $pendingAssessments,
    ]);
})->middleware(['auth', 'verified'])->name('student.activities');

Route::get('/student/assessments/{assessment}', function (Assessment $assessment) {
    abort_unless(auth()->user()?->isStudent(), 403);
    abort_unless($assessment->status === 'published', 404);

    return view('student.assessment', [
        'assessment' => $assessment,
    ]);
})->middleware(['auth', 'verified'])->name('student.assessments.show');

Route::get('/student/rewards', function () {
    return view('student.rewards');
})->middleware(['auth', 'verified'])->name('student.rewards');

Route::get('/teacher/students', function () {
    $students = User::query()
        ->where('role', 'student')
        ->orderBy('name')
        ->get();

    return view('students.index', [
        'students' => $students,
    ]);
})->middleware(['auth', 'verified'])->name('students.index');

Route::get('/teacher/students/{student}', function (User $student) {
    abort_unless($student->isStudent(), 404);

    return view('students.show', [
        'student' => $student,
    ]);
})->middleware(['auth', 'verified'])->name('students.show');

Route::get('/teacher/reports', function () {
    return view('reports.index');
})->middleware(['auth', 'verified'])->name('reports.index');

Route::get('/teacher/reports/juan-dela-cruz', function () {
    return view('reports.student', [
        'student' => [
            'name' => 'Juan Dela Cruz',
            'grade' => 'Grade 6-B',
            'student_id' => '#2024-0082',
            'points' => '12,450',
            'level' => 'Level 24',
            'xp_to_next' => '850 XP to next level',
            'literacy' => 88,
            'numeracy' => 72,
            'accuracy' => '90%',
            'response_time' => '14.2s',
        ],
    ]);
})->middleware(['auth', 'verified'])->name('reports.student');

Route::get('/teacher/assessments', [AssessmentController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('assessments.index');

Route::post('/teacher/assessments', [AssessmentController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('assessments.store');

Route::get('/teacher/assessments/{assessment}', [AssessmentController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('assessments.show');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
