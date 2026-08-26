<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $pendingRequests = User::query()
            ->whereIn('role', ['student', 'teacher'])
            ->where('approval_status', 'pending')
            ->latest()
            ->paginate(8);

        $pendingCount = User::query()
            ->whereIn('role', ['student', 'teacher'])
            ->where('approval_status', 'pending')
            ->count();

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

        return view('admin.token-requests', [
            'adminUser' => $adminUser,
            'adminInitials' => $this->initials($adminUser->name),
            'pendingRequests' => $pendingRequests,
            'pendingCount' => $pendingCount,
            'pendingTrend' => $this->buildTrend($pendingCurrentWeek, $pendingPreviousWeek),
            'approvedToday' => $approvedToday,
            'approvedAccounts' => $approvedAccounts,
        ]);
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless(in_array($user->role, ['student', 'teacher'], true) && $user->isPendingApproval(), 404);

        $validated = $request->validate([
            'section' => [
                $user->isTeacher() ? 'required' : 'nullable',
                'string',
                Rule::in(['Section A', 'Section B', 'Section C']),
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
