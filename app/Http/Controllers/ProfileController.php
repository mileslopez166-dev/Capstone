<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ProfileChangeRequest;
use App\Models\User;
use App\Support\NotificationSender;
use App\Support\StudentLeaderboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'pendingProfileChange' => ($user->isStudent() || $user->isTeacher())
                ? $user->profileChangeRequests()->pending()->latest()->first()
                : null,
            'studentRank' => $studentRank,
            'studentRankTier' => $this->rankTier($studentRank),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($user->isStudent() || $user->isTeacher()) {
            $pending = $user->profileChangeRequests()->pending()->latest()->first();
            $requestedName = trim($validated['name']);
            $requestedEmail = strtolower($validated['email']);
            $hasChanges = $requestedName !== $user->name || $requestedEmail !== strtolower($user->email);

            if (! $hasChanges) {
                if ($pending) {
                    $pending->update(['status' => 'cancelled', 'reviewed_at' => now()]);

                    return Redirect::route('profile.edit')->with('status', 'profile-change-cancelled');
                }

                return Redirect::route('profile.edit')->with('status', 'profile-change-unchanged');
            }

            $changeRequest = ProfileChangeRequest::updateOrCreate([
                'user_id' => $user->id,
                'status' => 'pending',
            ], [
                'current_name' => $user->name,
                'current_email' => $user->email,
                'requested_name' => $requestedName,
                'requested_email' => $requestedEmail,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
            NotificationSender::notifyAdminsAboutProfileChange($changeRequest);

            return Redirect::route('profile.edit')->with('status', 'profile-change-requested');
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

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
        return StudentLeaderboard::entries()->firstWhere('student.id', $student->id)['rank'] ?? null;
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
