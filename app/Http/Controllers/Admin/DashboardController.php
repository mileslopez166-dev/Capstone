<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $adminUser = $request->user();
        $now = CarbonImmutable::now();
        $currentWindowStart = $now->subDays(30);
        $previousWindowStart = $currentWindowStart->subDays(30);

        $totalUsers = User::query()->count();
        $adminCount = User::query()->where('role', 'admin')->count();
        $teacherCount = User::query()->where('role', 'teacher')->count();
        $studentCount = User::query()->where('role', 'student')->count();
        $verifiedUsers = User::query()->whereNotNull('email_verified_at')->count();
        $verifiedUsersCurrent = User::query()
            ->whereNotNull('email_verified_at')
            ->where('created_at', '>=', $currentWindowStart)
            ->count();
        $verifiedUsersPrevious = User::query()
            ->whereNotNull('email_verified_at')
            ->whereBetween('created_at', [$previousWindowStart, $currentWindowStart])
            ->count();

        $sessionLifetime = (int) config('session.lifetime', 120);
        $activeSessions = DB::table('sessions')
            ->where('last_activity', '>=', $now->subMinutes($sessionLifetime)->timestamp)
            ->count();
        $previousActiveSessions = DB::table('sessions')
            ->whereBetween('last_activity', [
                $now->subMinutes($sessionLifetime * 2)->timestamp,
                $now->subMinutes($sessionLifetime)->timestamp - 1,
            ])
            ->count();

        $newUsersCurrent = User::query()
            ->where('created_at', '>=', $currentWindowStart)
            ->count();
        $newUsersPrevious = User::query()
            ->whereBetween('created_at', [$previousWindowStart, $currentWindowStart])
            ->count();

        $publishedAssessments = Assessment::query()->where('status', 'published')->count();
        $draftAssessments = Assessment::query()->where('status', 'draft')->count();
        $literacyAssessments = Assessment::query()->where('subject', 'literacy')->count();
        $numeracyAssessments = Assessment::query()->where('subject', 'numeracy')->count();
        $publishedCurrent = Assessment::query()
            ->where('status', 'published')
            ->where('created_at', '>=', $currentWindowStart)
            ->count();
        $publishedPrevious = Assessment::query()
            ->where('status', 'published')
            ->whereBetween('created_at', [$previousWindowStart, $currentWindowStart])
            ->count();

        $growthSeries = $this->buildGrowthSeries($now);
        $maxGrowth = max(1, $growthSeries->max('count'));
        $growthSeries = $growthSeries->map(function (array $point) use ($maxGrowth): array {
            $point['height'] = $point['count'] === 0
                ? 0
                : max(12, (int) round(($point['count'] / $maxGrowth) * 100));

            return $point;
        });

        $summaryCards = [
            [
                'title' => 'Verified Accounts',
                'value' => number_format($verifiedUsers),
                'detail' => $this->formatPercentage($totalUsers > 0 ? ($verifiedUsers / $totalUsers) * 100 : 0).' of all users',
                'icon' => 'verified',
                'border' => 'border-secondary',
                'icon_bg' => 'bg-secondary-container',
                'icon_text' => 'text-secondary',
                'badge' => $this->buildTrend($verifiedUsersCurrent, $verifiedUsersPrevious),
            ],
            [
                'title' => 'Active Sessions',
                'value' => number_format($activeSessions),
                'detail' => 'Current window: '.number_format($sessionLifetime).' min',
                'icon' => 'moving',
                'border' => 'border-primary',
                'icon_bg' => 'bg-primary-container/30',
                'icon_text' => 'text-primary',
                'badge' => $this->buildTrend($activeSessions, $previousActiveSessions),
            ],
            [
                'title' => 'Total Users',
                'value' => number_format($totalUsers),
                'detail' => number_format($adminCount).' admins',
                'icon' => 'groups',
                'border' => 'border-tertiary-container',
                'icon_bg' => 'bg-tertiary-container/40',
                'icon_text' => 'text-tertiary-dim',
                'badge' => $this->buildTrend($newUsersCurrent, $newUsersPrevious),
            ],
            [
                'title' => 'Published Assessments',
                'value' => number_format($publishedAssessments),
                'detail' => number_format($draftAssessments).' drafts pending',
                'icon' => 'assignment_turned_in',
                'border' => 'border-error-container',
                'icon_bg' => 'bg-error-container/20',
                'icon_text' => 'text-error',
                'badge' => $this->buildTrend($publishedCurrent, $publishedPrevious),
            ],
        ];

        $activityFeed = $this->buildActivityFeed();

        $roleDistribution = collect([
            ['label' => 'Admins', 'count' => $adminCount, 'color' => '#005e9f'],
            ['label' => 'Teachers', 'count' => $teacherCount, 'color' => '#006b1b'],
            ['label' => 'Students', 'count' => $studentCount, 'color' => '#ffeb3b'],
        ])->map(function (array $role) use ($totalUsers): array {
            $role['percentage'] = $totalUsers > 0 ? round(($role['count'] / $totalUsers) * 100, 1) : 0.0;

            return $role;
        });

        $roleDonutStyle = $this->buildDonutStyle($roleDistribution);
        $totalAssessments = $publishedAssessments + $draftAssessments;
        $assessmentPipeline = [
            [
                'label' => 'Published',
                'value' => $publishedAssessments,
                'total' => $totalAssessments,
                'percentage' => $totalAssessments > 0 ? round(($publishedAssessments / $totalAssessments) * 100) : 0,
                'bar_class' => 'bg-primary',
                'badge' => $publishedAssessments > 0 ? 'Live' : 'Idle',
            ],
            [
                'label' => 'Draft',
                'value' => $draftAssessments,
                'total' => $totalAssessments,
                'percentage' => $totalAssessments > 0 ? round(($draftAssessments / $totalAssessments) * 100) : 0,
                'bar_class' => 'bg-secondary',
                'badge' => $draftAssessments > 0 ? 'Queued' : 'None',
            ],
            [
                'label' => 'Literacy',
                'value' => $literacyAssessments,
                'total' => $totalAssessments,
                'percentage' => $totalAssessments > 0 ? round(($literacyAssessments / $totalAssessments) * 100) : 0,
                'bar_class' => 'bg-tertiary-container',
                'badge' => 'Subject Mix',
            ],
            [
                'label' => 'Numeracy',
                'value' => $numeracyAssessments,
                'total' => $totalAssessments,
                'percentage' => $totalAssessments > 0 ? round(($numeracyAssessments / $totalAssessments) * 100) : 0,
                'bar_class' => 'bg-error-container',
                'badge' => 'Subject Mix',
            ],
        ];

        return view('admin.dashboard', [
            'adminUser' => $adminUser,
            'adminInitials' => $this->initials($adminUser->name),
            'summaryCards' => $summaryCards,
            'growthSeries' => $growthSeries,
            'growthYAxis' => $this->buildYAxisLabels($maxGrowth),
            'growthMax' => $maxGrowth,
            'activityFeed' => $activityFeed,
            'roleDistribution' => $roleDistribution,
            'roleDonutStyle' => $roleDonutStyle,
            'totalUsers' => $totalUsers,
            'assessmentPipeline' => $assessmentPipeline,
        ]);
    }

    private function buildGrowthSeries(CarbonImmutable $now): Collection
    {
        $seriesStart = $now->startOfDay()->subDays(27);

        return collect(range(0, 3))->map(function (int $index) use ($seriesStart): array {
            $bucketStart = $seriesStart->addDays($index * 7);
            $bucketEnd = $bucketStart->addDays(7);
            $count = User::query()
                ->where('created_at', '>=', $bucketStart)
                ->where('created_at', '<', $bucketEnd)
                ->count();

            return [
                'label' => 'W'.($index + 1),
                'range' => $bucketStart->format('M j'),
                'count' => $count,
            ];
        });
    }

    private function buildActivityFeed(): Collection
    {
        $recentUsers = User::query()
            ->latest()
            ->take(3)
            ->get()
            ->map(function (User $user): array {
                return [
                    'occurred_at' => $user->created_at,
                    'title' => $user->name.' joined the platform',
                    'description' => Str::headline($user->role).' account created for '.$user->email.'.',
                    'time' => $user->created_at?->diffForHumans() ?? 'Just now',
                    'icon' => match ($user->role) {
                        'admin' => 'admin_panel_settings',
                        'teacher' => 'co_present',
                        default => 'person_add',
                    },
                    'icon_bg' => match ($user->role) {
                        'admin' => 'bg-primary-container/30',
                        'teacher' => 'bg-secondary-container',
                        default => 'bg-tertiary-container/50',
                    },
                    'icon_text' => match ($user->role) {
                        'admin' => 'text-primary',
                        'teacher' => 'text-secondary',
                        default => 'text-tertiary',
                    },
                ];
            });

        $recentAssessments = Assessment::query()
            ->with('teacher')
            ->latest()
            ->take(3)
            ->get()
            ->map(function (Assessment $assessment): array {
                $isPublished = $assessment->status === 'published';

                return [
                    'occurred_at' => $assessment->created_at,
                    'title' => $isPublished ? 'Assessment published' : 'Assessment saved as draft',
                    'description' => '"'.$assessment->title.'" by '.($assessment->teacher?->name ?? 'Unknown teacher').'.',
                    'time' => $assessment->created_at?->diffForHumans() ?? 'Just now',
                    'icon' => $isPublished ? 'assignment_turned_in' : 'edit_note',
                    'icon_bg' => $isPublished ? 'bg-secondary-container' : 'bg-surface-container-high',
                    'icon_text' => $isPublished ? 'text-secondary' : 'text-on-surface-variant',
                ];
            });

        return $recentUsers
            ->concat($recentAssessments)
            ->sortByDesc('occurred_at')
            ->take(5)
            ->values();
    }

    private function buildYAxisLabels(int $max): array
    {
        return [
            $max,
            (int) ceil($max * 0.75),
            (int) ceil($max * 0.5),
            (int) ceil($max * 0.25),
            0,
        ];
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
            'label' => ($isPositive ? '+' : '').$this->formatPercentage($change),
            'classes' => $isPositive
                ? 'text-secondary bg-secondary-container'
                : 'text-error bg-error-container/20',
        ];
    }

    private function buildDonutStyle(Collection $distribution): string
    {
        if ($distribution->sum('count') === 0) {
            return 'background: conic-gradient(#dfe3e7 0% 100%);';
        }

        $start = 0.0;
        $stops = [];

        foreach ($distribution as $slice) {
            $end = $start + $slice['percentage'];
            $stops[] = "{$slice['color']} {$start}% {$end}%";
            $start = $end;
        }

        return 'background: conic-gradient('.implode(', ', $stops).');';
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

    private function formatPercentage(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1), '0'), '.').'%';
    }
}
