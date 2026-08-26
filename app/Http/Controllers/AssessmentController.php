<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isTeacher(), 403);

        $assessments = Assessment::query()
            ->where('created_by', $request->user()->id)
            ->latest()
            ->get();

        return view('assessments.index', [
            'assessments' => $assessments,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isTeacher(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'in:literacy,numeracy'],
            'quiz_type' => ['required', Rule::in(['multiple_choice', 'data_egg', 'flashcards'])],
            'delivery_method' => ['required', Rule::in(['upload', 'manual'])],
            'target_section' => ['required', Rule::in(['all', 'section_a', 'section_b', 'section_c'])],
            'focus_areas' => ['required', 'array', 'min:1'],
            'focus_areas.*' => ['string', Rule::in([
                'Reading Fluency',
                'Comprehension Depth',
                'Spelling & Vocabulary',
                'Mental Arithmetic',
                'Problem Solving',
                'Data Interpretation',
            ])],
            'assessment_asset' => ['required_if:delivery_method,upload', 'nullable', 'file', 'mimes:csv,pdf,json,jpg,jpeg,png', 'max:10240'],
            'manual_questions' => ['required_if:delivery_method,manual', 'nullable', 'array', 'min:1'],
            'manual_questions.*.question' => ['required_if:delivery_method,manual', 'nullable', 'string', 'max:1000'],
            'manual_questions.*.answers.A' => ['required_if:delivery_method,manual', 'nullable', 'string', 'max:255'],
            'manual_questions.*.answers.B' => ['required_if:delivery_method,manual', 'nullable', 'string', 'max:255'],
            'manual_questions.*.answers.C' => ['required_if:delivery_method,manual', 'nullable', 'string', 'max:255'],
            'manual_questions.*.answers.D' => ['required_if:delivery_method,manual', 'nullable', 'string', 'max:255'],
            'manual_questions.*.correct_answer' => ['required_if:delivery_method,manual', 'nullable', Rule::in(['A', 'B', 'C', 'D'])],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $assetPath = $request->hasFile('assessment_asset')
            ? $request->file('assessment_asset')->store('assessment-assets', 'public')
            : null;

        $manualQuestions = $validated['delivery_method'] === 'manual'
            ? collect($validated['manual_questions'] ?? [])
                ->values()
                ->map(fn (array $question): array => [
                    'question' => $question['question'] ?? '',
                    'answers' => [
                        'A' => $question['answers']['A'] ?? '',
                        'B' => $question['answers']['B'] ?? '',
                        'C' => $question['answers']['C'] ?? '',
                        'D' => $question['answers']['D'] ?? '',
                    ],
                    'correct_answer' => $question['correct_answer'] ?? null,
                ])
                ->all()
            : null;

        $request->user()->createdAssessments()->create([
            'title' => $validated['title'],
            'subject' => $validated['subject'],
            'quiz_type' => $validated['quiz_type'],
            'delivery_method' => $validated['delivery_method'],
            'target_section' => $validated['target_section'],
            'focus_areas' => $validated['focus_areas'],
            'asset_path' => $assetPath,
            'manual_questions' => $manualQuestions,
            'instructions' => $validated['instructions'] ?? null,
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('assessments.index')
            ->with('status', 'Assessment created successfully.');
    }

    public function show(Request $request, Assessment $assessment): View
    {
        abort_unless($request->user()?->isTeacher(), 403);
        abort_unless($assessment->created_by === $request->user()->id, 404);

        return view('assessments.show', [
            'assessment' => $assessment,
        ]);
    }
}
