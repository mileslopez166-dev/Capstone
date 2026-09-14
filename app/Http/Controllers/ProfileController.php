<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $studentRank = null;

        if ($user->isStudent()) {
            $studentRank = $this->studentLeaderboardRank($user);
        }

        return view('profile.edit', [
            'user' => $user,
            'studentRank' => $studentRank,
            'studentRankTier' => $this->rankTier($studentRank),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function studentLeaderboardRank(User $student): ?int
    {
        $students = User::query()
            ->where('role', 'student')
            ->where('approval_status', 'approved')
            ->orderBy('name')
            ->get();

        $submissions = AssessmentSubmission::query()
            ->whereIn('user_id', $students->pluck('id'))
            ->get()
            ->groupBy('user_id');

        $rankedStudents = $students
            ->map(function (User $rankedStudent) use ($submissions): array {
                $studentSubmissions = $submissions->get($rankedStudent->id, collect());

                return [
                    'student' => $rankedStudent,
                    'points' => (int) $studentSubmissions->sum('points'),
                    'completed' => $studentSubmissions->count(),
                ];
            })
            ->filter(fn (array $entry): bool => $entry['completed'] > 0)
            ->sortBy([
                ['points', 'desc'],
                ['completed', 'desc'],
                fn (array $entry): string => $entry['student']->name,
            ])
            ->values();

        $rank = $rankedStudents->search(fn (array $entry): bool => $entry['student']->is($student));

        return $rank === false ? null : $rank + 1;
    }

    private function rankTier(?int $rank): array
    {
        return match ($rank) {
            1 => ['label' => 'Flaming', 'icon' => 'local_fire_department'],
            2 => ['label' => 'Diamond', 'icon' => 'diamond'],
            3 => ['label' => 'Platinum', 'icon' => 'workspace_premium'],
            4 => ['label' => 'Gold', 'icon' => 'military_tech'],
            5 => ['label' => 'Silver', 'icon' => 'shield'],
            default => ['label' => 'Bronze', 'icon' => 'editor_choice'],
        };
    }
}