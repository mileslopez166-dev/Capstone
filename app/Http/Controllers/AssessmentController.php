<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'instructions' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $request->user()->createdAssessments()->create($validated);

        return redirect()
            ->route('assessments.index')
            ->with('status', 'Assessment created successfully.');
    }
}
