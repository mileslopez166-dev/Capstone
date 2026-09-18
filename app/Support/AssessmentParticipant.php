<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Http\Request;

class AssessmentParticipant
{
    public static function matchesSection(Assessment $assessment, User $student): bool
    {
        $section = $student->section ? strtolower(str_replace(' ', '_', $student->section)) : null;

        return in_array($assessment->target_section, ['all', null], true)
            || ($section && $assessment->target_section === $section);
    }

    public static function resolve(Request $request, Assessment $assessment, ?User $student = null): User
    {
        if ($student !== null) {
            // Only explicit teacher routes may select someone other than the authenticated user.
            abort_unless($request->routeIs('teacher.assessments.*') && $request->user()?->isTeacher()
                && $request->user()->isApproved(), 403);
            abort_unless($assessment->created_by === $request->user()->id, 404);
            abort_unless($student->isStudent() && $student->isApproved(), 404);
        } else {
            abort_unless($request->user()?->isStudent(), 403);
            $student = $request->user();
        }

        abort_unless($assessment->status === 'published' && self::matchesSection($assessment, $student), 404);

        return $student;
    }
}
