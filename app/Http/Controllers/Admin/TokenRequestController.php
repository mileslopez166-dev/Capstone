<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfileChangeRequest;
use App\Models\User;
use App\Support\NotificationSender;
use App\Support\SectionCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TokenRequestController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $adminUser = $request->user();
        $now = CarbonImmutable::now();
        $currentWeekStart = $now->subDays(7);
        $previousWeekStart = $currentWeekStart->subDays(7);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
        ];

        $pendingRequests = User::query()
            ->whereIn('role', ['student', 'teacher'])
            ->where('approval_status', 'pending')
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $query->where(function ($query) use ($filters): void {
                    $query->where('name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('email', 'like', '%'.$filters['search'].'%')
                        ->orWhere('role', 'like', '%'.$filters['search'].'%')
                        ->orWhere('section', 'like', '%'.$filters['search'].'%');
                });
            })
            ->latest()
            ->paginate(8)
            ->withQueryString();

        $pendingCount = User::query()
            ->whereIn('role', ['student', 'teacher'])
            ->where('approval_status', 'pending')
            ->count();

        $profileChangeRequests = ProfileChangeRequest::query()
            ->with('user')
            ->pending()
            ->whereHas('user', function ($query) use ($filters): void {
                $query->whereIn('role', ['student', 'teacher'])
                    ->when($filters['search'] !== '', function ($query) use ($filters): void {
                        $query->where(function ($query) use ($filters): void {
                            $query->where('name', 'like', '%'.$filters['search'].'%')
                                ->orWhere('email', 'like', '%'.$filters['search'].'%')
                                ->orWhere('role', 'like', '%'.$filters['search'].'%')
                                ->orWhere('section', 'like', '%'.$filters['search'].'%');
                        });
                    });
            })
            ->latest()
            ->paginate(8, ['*'], 'profile_page')
            ->withQueryString();

        $pendingProfileChangeCount = ProfileChangeRequest::query()->pending()->count();

        $pendingCurrentWeek = User::query()
            ->whereIn('role', ['student', 'teacher'])
            ->where('approval_status', 'pending')
            ->where('created_at', '>=', $currentWeekStart)
            ->count();

        $pendingPreviousWeek = User::query()
            ->whereIn('role', ['student', 'teacher'])
            ->where('approval_status', 'pending')
            ->whereBetween('created_at', [$previousWeekStart, $currentWeekStart])
            ->count();

        $approvedToday = User::query()
            ->whereIn('role', ['student', 'teacher'])
            ->where('approval_status', 'approved')
            ->whereDate('approved_at', $now->toDateString())
            ->count();

        $approvedAccounts = User::query()
            ->whereIn('role', ['student', 'teacher'])
            ->where('approval_status', 'approved')
            ->count();

        $searchSuggestions = $this->buildSearchSuggestions();

        return view('admin.token-requests', [
            'adminUser' => $adminUser,
            'adminInitials' => $this->initials($adminUser->name),
            'pendingRequests' => $pendingRequests,
            'pendingCount' => $pendingCount,
            'profileChangeRequests' => $profileChangeRequests,
            'pendingProfileChangeCount' => $pendingProfileChangeCount,
            'pendingTrend' => $this->buildTrend($pendingCurrentWeek, $pendingPreviousWeek),
            'approvedToday' => $approvedToday,
            'approvedAccounts' => $approvedAccounts,
            'filters' => $filters,
            'searchSuggestions' => $searchSuggestions,
        ]);
    }

    private function buildSearchSuggestions(): \Illuminate\Support\Collection
    {
        $shortcuts = collect([
            'Student',
            'Teacher',
            ...SectionCatalog::labels(),
            'Pending Approvals',
        ]);

        $pendingUsers = User::query()
            ->select(['name', 'email', 'role', 'section'])
            ->whereIn('role', ['student', 'teacher'])
            ->where('approval_status', 'pending')
            ->latest()
            ->take(15)
            ->get()
            ->flatMap(fn (User $user): array => [$user->name, $user->email, ucfirst($user->role), $user->section]);

        $profileUsers = ProfileChangeRequest::query()
            ->with('user:id,name,email,role,section')
            ->pending()
            ->latest()
            ->take(15)
            ->get()
            ->flatMap(fn (ProfileChangeRequest $change): array => array_filter([
                $change->user?->name,
                $change->user?->email,
                $change->requested_name,
                $change->requested_email,
            ]));

        return $shortcuts
            ->concat($pendingUsers)
            ->concat($profileUsers)
            ->filter()
            ->unique()
            ->values();
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless(in_array($user->role, ['student', 'teacher'], true) && $user->isPendingApproval(), 404);

        $validated = $request->validate([
            'section' => [
                $user->isTeacher() ? 'required' : 'nullable',
                'string',
                Rule::in(SectionCatalog::labels()),
            ],
        ]);

        $changes = [
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ];

        if ($user->isTeacher()) {
            $changes['section'] = $validated['section'];
        }

        $user->forceFill($changes)->save();

        if ($user->isStudent()) {
            NotificationSender::notifyStudentEnrollmentApproved($user, $request->user());
        }

        return redirect()
            ->route('admin.token-requests.index')
            ->with('status', "{$user->name} has been approved and can now sign in.");
    }

    public function decline(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless(in_array($user->role, ['student', 'teacher'], true) && $user->isPendingApproval(), 404);

        $user->forceFill([
            'approval_status' => 'rejected',
            'approved_at' => null,
            'approved_by' => $request->user()->id,
        ])->save();

        return redirect()
            ->route('admin.token-requests.index')
            ->with('status', "{$user->name}'s request has been declined.");
    }

    public function approveProfileChange(Request $request, ProfileChangeRequest $profileChangeRequest): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($profileChangeRequest->status === 'pending', 404);

        Validator::make(['email' => $profileChangeRequest->requested_email], [
            'email' => ['required', 'email', Rule::unique(User::class)->ignore($profileChangeRequest->user_id)],
        ])->validate();

        DB::transaction(function () use ($request, $profileChangeRequest): void {
            $change = ProfileChangeRequest::query()->lockForUpdate()->findOrFail($profileChangeRequest->id);
            abort_unless($change->status === 'pending', 404);
            $user = User::query()->lockForUpdate()->findOrFail($change->user_id);
            $emailChanged = strcasecmp($user->email, $change->requested_email) !== 0;

            $user->forceFill([
                'name' => $change->requested_name,
                'email' => $change->requested_email,
                'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
            ])->save();
            $change->update([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        });

        $profileChangeRequest->refresh()->load('user');
        NotificationSender::notifyProfileChangeReviewed($profileChangeRequest);

        return redirect()->route('admin.token-requests.index')->with('status', 'The profile change has been approved.');
    }

    public function declineProfileChange(Request $request, ProfileChangeRequest $profileChangeRequest): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($profileChangeRequest->status === 'pending', 404);

        $profileChangeRequest->update([
            'status' => 'declined',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);
        $profileChangeRequest->load('user');
        NotificationSender::notifyProfileChangeReviewed($profileChangeRequest);

        return redirect()->route('admin.token-requests.index')->with('status', 'The profile change has been declined.');
    }

    private function initials(string $name): string
    {
        return Str::of($name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $segment) => Str::upper(Str::substr($segment, 0, 1)))
            ->implode('');
    }

    private function buildTrend(int $current, int $previous): array
    {
        if ($previous === 0) {
            $change = $current > 0 ? 100.0 : 0.0;
        } else {
            $change = (($current - $previous) / $previous) * 100;
        }

        $isPositive = $change >= 0;

        return [
            'label' => ($isPositive ? '+' : '').rtrim(rtrim(number_format($change, 1), '0'), '.').'%',
            'is_positive' => $isPositive,
        ];
    }
}
