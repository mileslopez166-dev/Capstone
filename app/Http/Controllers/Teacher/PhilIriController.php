<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AssessmentSubmission;
use App\Support\PhilIri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PhilIriController extends Controller
{
    private function authorizeSubmission(Request $request, AssessmentSubmission $submission): void
    {
        abort_unless($request->user()->isTeacher(), 403);
        abort_unless($submission->assessment?->created_by === $request->user()->id, 404);
        abort_unless($submission->assessment->subject === 'literacy', 404);
    }

    public function show(Request $request, AssessmentSubmission $submission)
    {
        $this->authorizeSubmission($request, $submission);

        return view('teacher.phil-iri', [
            'submission' => $submission->load('student'),
            'result' => PhilIri::forSubmission($submission),
        ]);
    }

    public function update(Request $request, AssessmentSubmission $submission)
    {
        $this->authorizeSubmission($request, $submission);
        abort_unless($submission->assessment->assessment_type === 'oral_reading', 422);
        $rules = [
            'word_count' => ['required', 'integer', 'min:1', 'max:10000'],
            'miscues' => ['required', 'integer', 'min:0', 'lte:word_count'],
            'reading_seconds' => ['nullable', 'integer', 'min:1', 'max:604800'],
            'comprehension_correct' => ['prohibited'],
            'comprehension_questions' => ['prohibited'],
        ];
        if ((int) $submission->question_count === 0) {
            $rules['comprehension_correct'] = ['nullable', 'required_with:comprehension_questions', 'integer', 'min:0', 'lte:comprehension_questions'];
            $rules['comprehension_questions'] = ['nullable', 'required_with:comprehension_correct', 'integer', 'min:1', 'max:1000'];
        }
        $data = $request->validate($rules);

        DB::transaction(function () use ($submission, $request, $data) {
            $attempt = AssessmentSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $result = PhilIri::forSubmission($attempt);
            $result['word_count'] = (int) $data['word_count'];
            $result['miscues'] = (int) $data['miscues'];
            $result['reading_seconds'] = isset($data['reading_seconds']) ? (int) $data['reading_seconds'] : null;
            if ((int) $attempt->question_count === 0) {
                $result['correct_count'] = (int) ($data['comprehension_correct'] ?? 0);
                $result['question_count'] = (int) ($data['comprehension_questions'] ?? 0);
            }
            $result['reviewed_by'] = $request->user()->id;
            $result['reviewed_at'] = now()->toIso8601String();
            $attempt->update(['phil_iri' => PhilIri::calculate($result)]);
        });

        return redirect()->route('teacher.phil-iri.show', $submission)->with('status', 'Phil-IRI scoring saved.');
    }
}
