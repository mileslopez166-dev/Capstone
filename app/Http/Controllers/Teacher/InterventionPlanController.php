<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\InterventionPlan;
use App\Support\AssessmentParticipant;
use App\Support\InterventionFollowUp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InterventionPlanController extends Controller
{
    public function update(Request $request, AssessmentSubmission $submission): RedirectResponse
    {
        abort_unless($request->user()->isTeacher(), 403);
        abort_unless($submission->assessment?->created_by === $request->user()->id
            && $submission->student?->isStudent(), 404);

        $data = $request->validateWithBag('intervention-'.$submission->id, [
            'intervention.type' => ['required', Rule::in(array_keys(InterventionPlan::TYPES))],
            'intervention.notes' => ['required', 'string', 'max:5000'],
            'intervention.follow_up_date' => ['nullable', 'date_format:Y-m-d'],
            'intervention.status' => ['required', Rule::in(array_keys(InterventionPlan::STATUSES))],
            'intervention.follow_up_assessment_id' => ['nullable', 'integer'],
            'intervention.comparison_basis' => ['nullable', 'string', 'max:1000'],
            'return_to' => ['nullable', Rule::in(['profile', 'report'])],
        ], [], [
            'intervention.type' => 'intervention type',
            'intervention.notes' => 'action plan notes',
            'intervention.follow_up_date' => 'follow-up date',
            'intervention.status' => 'progress status',
            'intervention.follow_up_assessment_id' => 'follow-up assessment',
            'intervention.comparison_basis' => 'comparison basis',
        ]);

        DB::transaction(function () use ($submission, $data) {
            $attempt = AssessmentSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $plan = $attempt->interventionPlan()->firstOrNew();
            $attributes = $data['intervention'];
            if (array_key_exists('follow_up_assessment_id', $attributes)) {
                $targetId = $attributes['follow_up_assessment_id'] ? (int) $attributes['follow_up_assessment_id'] : null;
                $changed = $targetId !== $plan->follow_up_assessment_id;
                if ($targetId) {
                    $target = Assessment::find($targetId);
                    if (!$target || $target->created_by !== $submission->assessment->created_by
                        || !InterventionFollowUp::comparable($submission, $target)
                        || ($changed && ($target->status !== 'published' || !AssessmentParticipant::matchesSection($target, $submission->student)))) {
                        throw ValidationException::withMessages(['intervention.follow_up_assessment_id' => 'Choose an available assessment of the same subject and type that you created for this student.'])
                            ->errorBag('intervention-'.$submission->id);
                    }
                    $basis = array_key_exists('comparison_basis', $attributes) ? $attributes['comparison_basis'] : $plan->comparison_basis;
                    if ($targetId !== $submission->assessment_id && trim($basis ?? '') === '') {
                        throw ValidationException::withMessages(['intervention.comparison_basis' => 'For a different assessment, record how its skills and difficulty are comparable.'])
                            ->errorBag('intervention-'.$submission->id);
                    }
                }
                if ($changed || ($targetId && !$plan->follow_up_linked_at)) {
                    $plan->forceFill([
                        'follow_up_assessment_id' => $targetId,
                        'follow_up_linked_at' => $targetId ? now() : null,
                        'follow_up_submission_id' => null,
                    ]);
                }
                if (!$targetId || $targetId === $submission->assessment_id) $attributes['comparison_basis'] = null;
                unset($attributes['follow_up_assessment_id']);
            }
            $plan->fill($attributes);
            $attempt->interventionPlan()->save($plan);
        });

        $route = ($data['return_to'] ?? 'report') === 'profile' ? 'students.show' : 'reports.student';

        return redirect()->to(route($route, $submission->user_id).'#intervention-'.$submission->id)
            ->with('intervention_saved', $submission->id);
    }
}
