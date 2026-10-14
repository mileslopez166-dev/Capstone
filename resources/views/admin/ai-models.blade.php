<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"/><meta content="width=device-width, initial-scale=1.0" name="viewport"/><title>AI Model Evaluation | AI-PGAALS</title><x-admin-head /></head>
<body class="staff-theme admin-theme min-h-screen overflow-x-hidden bg-surface text-on-surface">
<div class="min-h-screen lg:flex">
<x-admin-sidebar active="ai-models" :admin-user="$adminUser" :admin-initials="$adminInitials" />
<div class="w-full lg:ml-64">
<x-admin-topbar title="AI Model Evaluation" :admin-user="$adminUser" :admin-initials="$adminInitials" />
<main class="mx-auto max-w-7xl space-y-10 p-5 sm:p-6 lg:p-8">
    <header class="flex flex-col gap-4 border-b border-outline-variant/20 pb-7 lg:flex-row lg:items-end lg:justify-between">
        <div><div class="admin-page-label"><span class="material-symbols-outlined" aria-hidden="true">model_training</span>Validated algorithm control</div><h1 class="font-headline text-3xl font-bold sm:text-4xl">AI Model Evaluation</h1><p class="mt-2 max-w-3xl text-on-surface-variant">Compare proficiency classifiers and recommendation approaches without changing official assessment scores.</p></div>
        <form method="POST" action="{{ route('admin.ai-models.recommendations.evaluate') }}">@csrf<button class="ui-button ui-button-secondary" type="submit"><span class="material-symbols-outlined" aria-hidden="true">recommend</span>Evaluate recommendations</button></form>
    </header>
    @if (session('status'))<p class="rounded-md border border-secondary/30 bg-secondary-container/30 px-4 py-3 font-semibold" role="status">{{ session('status') }}</p>@endif
    @if ($errors->any())<div class="rounded-md border border-error/30 bg-error-container/30 px-4 py-3 text-on-error-container" role="alert">{{ $errors->first() }}</div>@endif

    @php
        $classifierCatalog = [
            'decision_tree' => [
                'name' => 'Decision Tree',
                'purpose' => 'Classifies learner proficiency through interpretable decision rules.',
                'method' => 'Median imputation, balanced class weights, and tuned tree depth and leaf size.',
            ],
            'random_forest' => [
                'name' => 'Random Forest',
                'purpose' => 'Classifies learner proficiency by combining predictions from 200 decision trees.',
                'method' => 'Median imputation, balanced subsample weights, and tuned depth and leaf size.',
            ],
            'svm' => [
                'name' => 'Support Vector Machine',
                'purpose' => 'Classifies learner proficiency by separating proficiency groups in a scaled feature space.',
                'method' => 'Median imputation, standard scaling, balanced class weights, and an RBF kernel.',
            ],
        ];
        $recommendationCatalog = [
            'content_based' => [
                'name' => 'Content-Based Filtering',
                'purpose' => 'Ranks unfinished eligible activities using subject, focus area, difficulty, proficiency evidence, and completion history.',
                'method' => 'Base 0.15 + subject 0.40 + related focus 0.25 + matching difficulty 0.20.',
                'cold_start' => 'Supported',
            ],
            'collaborative' => [
                'name' => 'Collaborative Filtering',
                'purpose' => 'Finds learners with similar assessment results and ranks activities that helped those learners.',
                'method' => 'Cosine similarity over at least two shared activities, followed by similarity-weighted peer scores.',
                'cold_start' => 'Requires interaction history',
            ],
            'hybrid' => [
                'name' => 'Hybrid Recommendation',
                'purpose' => 'Combines content and collaborative evidence while retaining a content-based fallback for sparse histories.',
                'method' => 'alpha x normalized content score + (1 - alpha) x normalized collaborative score.',
                'cold_start' => 'Supported through fallback',
            ],
        ];
    @endphp

    <nav class="flex flex-wrap gap-2" aria-label="Algorithm evidence sections">
        <a class="ui-button ui-button-secondary" href="#classification-heading"><span class="material-symbols-outlined" aria-hidden="true">account_tree</span>Classification</a>
        <a class="ui-button ui-button-secondary" href="#recommendation-heading"><span class="material-symbols-outlined" aria-hidden="true">recommend</span>Recommendation</a>
        <a class="ui-button ui-button-secondary" href="#evaluation-history"><span class="material-symbols-outlined" aria-hidden="true">history</span>Evaluation history</a>
    </nav>

    <section class="space-y-6" aria-labelledby="classification-heading">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-xs font-bold uppercase tracking-widest text-primary">Section A</p><h2 id="classification-heading" class="font-headline text-2xl font-bold">Classification algorithm evidence</h2><p class="mt-1 text-sm text-on-surface-variant">Each trained classifier has its own table of dataset, validation, tuning, and performance evidence.</p></div>
            <div class="flex flex-wrap gap-2">@foreach (['literacy' => 'Literacy', 'numeracy' => 'Numeracy'] as $subject => $label)<form method="POST" action="{{ route('admin.ai-models.classification.evaluate') }}">@csrf<input type="hidden" name="subject" value="{{ $subject }}"><button class="ui-button {{ $subject === 'literacy' ? 'ui-button-primary' : 'ui-button-secondary' }}" type="submit"><span class="material-symbols-outlined" aria-hidden="true">play_arrow</span>Run {{ $label }}</button></form>@endforeach</div>
        </div>
        @foreach (['literacy' => 'Literacy', 'numeracy' => 'Numeracy'] as $subject => $label)
            @php $evaluation=$latestBySubject->get($subject); $active=$activeConfigurations->get('classification:'.$subject); $versions=$evaluation?->versions ?? collect(); @endphp
            <div class="space-y-4 border-t border-outline-variant/20 pt-5">
                <div class="flex flex-col gap-3 bg-surface-container-low p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div><h3 class="font-headline text-xl font-bold">{{ $label }}</h3><p class="text-sm text-on-surface-variant">{{ data_get($evaluation?->dataset_info, 'rows', data_get($evaluation?->dataset_info, 'rows_sent', 0)) }} labeled records / {{ data_get($evaluation?->dataset_info, 'learners', 0) }} learners</p></div>
                    <div class="flex max-w-full flex-wrap gap-2 text-xs font-bold uppercase tracking-wide"><span class="rounded-full bg-surface-container-high px-3 py-1.5">{{ str($evaluation?->status ?? 'not_trained')->replace('_',' ')->title() }}</span><span class="max-w-full break-all rounded-full bg-primary-container/25 px-3 py-1.5 text-primary">{{ $active?->active_key ? 'Active: '.$active->active_key : 'No active comparison model' }}</span></div>
                </div>
                @if ($evaluation?->error_message)<p class="rounded-md border border-error/25 bg-error-container/20 px-5 py-3 text-sm text-on-error-container">{{ $evaluation->error_message }}</p>@endif
                <div class="grid gap-5">
                @foreach ($classifierCatalog as $algorithm => $definition)
                    @php
                        $version = $versions->firstWhere('algorithm', $algorithm);
                        $metrics = $version?->metrics ?? [];
                        $dataset = data_get($version?->provenance, 'dataset') ?? $evaluation?->dataset_info ?? [];
                        $protocol = data_get($version?->provenance, 'protocol', []);
                        $parameters = data_get($metrics, 'best_parameters', []);
                        $isActive = $version && $active?->active_key === $version->model_name;
                    @endphp
                    <article class="overflow-hidden rounded-md border border-outline-variant/25 bg-surface-container-lowest">
                        <div class="flex items-start justify-between gap-3 border-b border-outline-variant/15 p-4">
                            <div><h4 class="font-headline text-lg font-bold">{{ $definition['name'] }}</h4><p class="mt-1 text-xs text-on-surface-variant">{{ $definition['purpose'] }}</p></div>
                            <div class="flex flex-col items-end gap-1 text-right"><span class="text-[10px] font-bold uppercase tracking-wide">{{ str($version?->status ?? 'not_trained')->replace('_',' ')->title() }}</span>@if ($evaluation?->recommended_algorithm === $algorithm)<span class="text-[10px] font-bold uppercase text-secondary-dim">Recommended</span>@endif</div>
                        </div>
                        <div class="ui-data-table-scroll" role="region" aria-label="{{ $definition['name'] }} {{ strtolower($label) }} evidence table" tabindex="0">
                            <table class="ui-data-table w-full text-sm">
                                <thead><tr><th>Evidence</th><th>Recorded value</th></tr></thead>
                                <tbody>
                                    <tr><td>Subject</td><td>{{ $label }}</td></tr>
                                    <tr><td>Processing method</td><td class="ui-table-copy">{{ $definition['method'] }}</td></tr>
                                    <tr><td>Labeled records</td><td>{{ data_get($dataset, 'rows', data_get($dataset, 'rows_sent', 0)) }}</td></tr>
                                    <tr><td>Distinct learners</td><td>{{ data_get($dataset, 'learners', 0) }}</td></tr>
                                    <tr><td>Development / holdout rows</td><td>{{ data_get($dataset, 'development_rows', 'Pending') }} / {{ data_get($dataset, 'held_out_rows', 'Pending') }}</td></tr>
                                    <tr><td>Class distribution</td><td class="ui-table-copy">{{ collect(data_get($dataset, 'class_distribution', []))->map(fn ($count, $level) => $level.': '.$count)->join(' / ') ?: 'Pending' }}</td></tr>
                                    <tr><td>Input features</td><td class="ui-table-copy">{{ collect(data_get($dataset, 'features', []))->map(fn ($feature) => str($feature)->replace('_', ' ')->title())->join(', ') ?: 'Pending' }}</td></tr>
                                    <tr><td>Validation method</td><td class="ui-table-copy">{{ data_get($protocol, 'model_selection', 'Pending') }}</td></tr>
                                    <tr><td>Validation folds</td><td>{{ data_get($metrics, 'folds', 'Pending') }}</td></tr>
                                    <tr><td>Selected parameters</td><td class="ui-table-copy">{{ collect($parameters)->map(fn ($value, $key) => str($key)->after('classifier__')->replace('_', ' ')->title().': '.(is_bool($value) ? ($value ? 'True' : 'False') : ($value ?? 'None')))->join(' / ') ?: 'Pending' }}</td></tr>
                                    <tr><td>Accuracy</td><td>{{ isset($metrics['accuracy']) ? number_format($metrics['accuracy'] * 100, 1).'%' : 'Pending' }}</td></tr>
                                    <tr><td>Macro precision</td><td>{{ isset($metrics['precision_macro']) ? number_format($metrics['precision_macro'] * 100, 1).'%' : 'Pending' }}</td></tr>
                                    <tr><td>Macro recall</td><td>{{ isset($metrics['recall_macro']) ? number_format($metrics['recall_macro'] * 100, 1).'%' : 'Pending' }}</td></tr>
                                    <tr><td>Macro F1</td><td class="font-bold text-primary">{{ isset($metrics['f1_macro']) ? number_format($metrics['f1_macro'] * 100, 1).'%' : 'Pending' }}</td></tr>
                                    <tr><td>F1 standard deviation</td><td>{{ isset($metrics['standard_deviation_f1']) ? number_format($metrics['standard_deviation_f1'] * 100, 2).'%' : 'Pending' }}</td></tr>
                                    <tr><td>Random seed</td><td>{{ data_get($version?->provenance, 'random_seed', 'Pending') }}</td></tr>
                                    <tr><td>Model version</td><td class="break-all">{{ $version?->version ?? 'Pending' }}</td></tr>
                                    <tr><td>Evaluated</td><td>{{ $evaluation?->completed_at?->format('M j, Y g:i A') ?? 'Pending' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-outline-variant/15 p-4">
                            @if ($version?->status === 'validated' && ! $isActive)<form method="POST" action="{{ route('admin.ai-models.classification.activate', $version) }}">@csrf<button class="ui-button ui-button-secondary w-full" type="submit">Activate {{ $definition['name'] }}</button></form>@elseif ($isActive)<p class="text-sm font-bold text-primary">Active model</p>@else<p class="text-xs text-on-surface-variant">Run the {{ $label }} evaluation to populate this table.</p>@endif
                        </div>
                    </article>
                @endforeach
                </div>
                @if ($evaluation?->final_test_metrics)
                    @php $final=$evaluation->final_test_metrics; @endphp
                    <div class="grid gap-5 rounded-md border border-outline-variant/20 bg-surface-container-lowest p-5 lg:grid-cols-[1fr_auto]"><div><h4 class="font-bold">Final untouched holdout</h4><p class="mt-1 text-sm text-on-surface-variant">Used once after validation selected {{ str($final['algorithm'])->replace('_',' ')->title() }}. {{ $final['rows'] }} rows and {{ $final['learners'] }} learners.</p><div class="mt-3 flex flex-wrap gap-5"><p class="text-2xl font-bold text-primary">{{ number_format($final['f1_macro']*100,1) }}% <span class="text-sm font-medium text-on-surface-variant">macro F1</span></p><p class="text-2xl font-bold text-on-surface">{{ number_format($final['accuracy']*100,1) }}% <span class="text-sm font-medium text-on-surface-variant">accuracy</span></p><p class="text-2xl font-bold text-on-surface">{{ number_format($final['prediction_latency_ms_per_row'] ?? 0,2) }} <span class="text-sm font-medium text-on-surface-variant">ms / prediction</span></p></div></div><table class="text-center text-xs"><caption class="mb-2 font-bold">Confusion matrix</caption><tbody>@foreach ($final['confusion_matrix'] as $row)<tr>@foreach ($row as $cell)<td class="border border-outline-variant/20 px-3 py-2">{{ $cell }}</td>@endforeach</tr>@endforeach</tbody></table></div>
                    @if ($final['per_class'] ?? null)<div class="overflow-x-auto border-t border-outline-variant/15 p-5" role="region" aria-label="Per-class model metrics" tabindex="0"><table class="ui-data-table w-full"><thead><tr><th>Class</th><th>Precision</th><th>Recall</th><th>F1</th><th>Support</th></tr></thead><tbody>@foreach ($final['per_class'] as $level=>$classMetrics)<tr><td>{{ $level }}</td><td>{{ number_format($classMetrics['precision']*100,1) }}%</td><td>{{ number_format($classMetrics['recall']*100,1) }}%</td><td>{{ number_format($classMetrics['f1']*100,1) }}%</td><td>{{ $classMetrics['support'] }}</td></tr>@endforeach</tbody></table></div>@endif
                @endif
                @if ($active?->previous_key)<form class="border-t border-outline-variant/15 p-4 text-right" method="POST" action="{{ route('admin.ai-models.rollback') }}">@csrf<input type="hidden" name="type" value="classification"><input type="hidden" name="subject" value="{{ $subject }}"><button class="text-sm font-bold text-primary" type="submit">Restore previous model</button></form>@endif
            </div>
        @endforeach
    </section>
    <section class="space-y-5 border-t border-outline-variant/20 pt-8" aria-labelledby="recommendation-heading">
        <div><p class="text-xs font-bold uppercase tracking-widest text-primary">Section B</p><h2 id="recommendation-heading" class="font-headline text-2xl font-bold">Recommendation algorithm evidence</h2><p class="mt-1 text-sm text-on-surface-variant">Each approach has its own table of interaction data, ranking method, measured quality, and fallback behavior.</p></div>
        <div class="grid gap-4 md:grid-cols-3">
        @foreach ($recommendationCatalog as $approach => $definition)
            @php $rec=$latestRecommendations->get($approach); $metrics=$rec?->metrics ?? []; $activeRec=$activeConfigurations->get('recommendation:all'); @endphp
            <article class="overflow-hidden rounded-md border border-outline-variant/25 bg-surface-container-lowest">
                <div class="flex items-start justify-between gap-3 border-b border-outline-variant/15 p-5"><div><h3 class="font-headline text-lg font-bold">{{ $definition['name'] }}</h3><p class="mt-1 text-xs text-on-surface-variant">{{ $definition['purpose'] }}</p></div><span class="text-[10px] font-bold uppercase">{{ str($rec?->status ?? 'not_evaluated')->replace('_',' ')->title() }}</span></div>
                <div class="ui-data-table-scroll" role="region" aria-label="{{ $definition['name'] }} evidence table" tabindex="0">
                    <table class="ui-data-table w-full text-sm">
                        <thead><tr><th>Evidence</th><th>Recorded value</th></tr></thead>
                        <tbody>
                            <tr><td>Ranking method</td><td class="ui-table-copy">{{ $definition['method'] }}</td></tr>
                            <tr><td>Interactions</td><td>{{ data_get($rec?->dataset_info, 'interactions', 0) }}</td></tr>
                            <tr><td>Learners</td><td>{{ data_get($rec?->dataset_info, 'learners', 0) }}</td></tr>
                            <tr><td>Eligible activities</td><td>{{ data_get($rec?->dataset_info, 'activities', 0) }}</td></tr>
                            <tr><td>Held-out interactions</td><td>{{ data_get($rec?->dataset_info, 'holdouts', 0) }}</td></tr>
                            <tr><td>Precision@5</td><td>{{ isset($metrics['precision_at_5']) ? number_format($metrics['precision_at_5'] * 100, 1).'%' : 'Pending' }}</td></tr>
                            <tr><td>Recall@5</td><td>{{ isset($metrics['recall_at_5']) ? number_format($metrics['recall_at_5'] * 100, 1).'%' : 'Pending' }}</td></tr>
                            <tr><td>NDCG@5</td><td class="font-bold text-primary">{{ isset($metrics['ndcg_at_5']) ? number_format($metrics['ndcg_at_5'] * 100, 1).'%' : 'Pending' }}</td></tr>
                            <tr><td>Catalog coverage</td><td>{{ isset($metrics['coverage']) ? number_format($metrics['coverage'] * 100, 1).'%' : 'Pending' }}</td></tr>
                            <tr><td>Response time</td><td>{{ isset($metrics['response_time_ms']) ? number_format($metrics['response_time_ms'], 2).' ms' : 'Pending' }}</td></tr>
                            <tr><td>Content fallback rate</td><td>{{ isset($metrics['fallback_rate']) ? number_format($metrics['fallback_rate'] * 100, 1).'%' : 'Pending' }}</td></tr>
                            @if ($approach === 'hybrid')<tr><td>Selected alpha</td><td>{{ isset($metrics['alpha']) ? number_format($metrics['alpha'], 2) : 'Pending' }}</td></tr>@endif
                            <tr><td>Cold-start behavior</td><td>{{ $definition['cold_start'] }}</td></tr>
                            <tr><td>Evaluation note</td><td class="ui-table-copy">{{ $rec?->notes ?: 'No evaluation note recorded.' }}</td></tr>
                            <tr><td>Evaluated</td><td>{{ $rec?->evaluated_at?->format('M j, Y g:i A') ?? 'Pending' }}</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-outline-variant/15 p-4">
                    @if ($rec?->status==='evaluated' && !str_starts_with($activeRec?->active_key ?? '',$approach))
                        <form method="POST" action="{{ route('admin.ai-models.recommendations.activate') }}">@csrf<input type="hidden" name="approach" value="{{ $approach }}">@if ($approach==='hybrid')<input type="hidden" name="alpha" value="{{ data_get($metrics,'alpha',.7) }}">@endif<button class="ui-button ui-button-secondary w-full" type="submit">Activate {{ $definition['name'] }}</button></form>
                    @elseif (str_starts_with($activeRec?->active_key ?? '',$approach))<p class="text-sm font-bold text-primary">Active approach</p>@else<p class="text-xs text-on-surface-variant">Run the recommendation evaluation to populate this table.</p>@endif
                </div>
            </article>
        @endforeach
        </div>
        @php $activeRec=$activeConfigurations->get('recommendation:all'); @endphp
        @if ($activeRec?->previous_key)<form class="text-right" method="POST" action="{{ route('admin.ai-models.rollback') }}">@csrf<input type="hidden" name="type" value="recommendation"><input type="hidden" name="subject" value="all"><button class="text-sm font-bold text-primary" type="submit">Restore previous recommendation approach</button></form>@endif
    </section>
    <section class="border-t border-outline-variant/20 pt-8"><h2 class="font-headline text-2xl font-bold">Recent learning recommendations</h2><div class="mt-4 overflow-x-auto" role="region" aria-label="Recent generated learning recommendations" tabindex="0"><table class="ui-data-table w-full"><thead><tr><th>Learner</th><th>Activity</th><th>Approach</th><th>Reason</th><th>Status</th></tr></thead><tbody>
        @forelse ($recentLearningRecommendations as $item)<tr><td>Student_{{ str_pad((string) $item->student_id, 3, '0', STR_PAD_LEFT) }}</td><td>{{ $item->assessment?->title ?? 'Removed activity' }}</td><td>{{ str($item->approach)->replace('_',' ')->title() }}</td><td>{{ collect($item->reasons ?? [])->take(2)->join(' / ') ?: 'No reason recorded' }}</td><td>{{ str($item->status)->title() }}</td></tr>@empty<tr><td colspan="5">No recommendations have been generated yet.</td></tr>@endforelse
    </tbody></table></div></section>
    <section id="evaluation-history" class="border-t border-outline-variant/20 pt-8"><h2 class="font-headline text-2xl font-bold">Evaluation history</h2><div class="mt-4 overflow-x-auto"><table class="ui-data-table w-full"><thead><tr><th>Type</th><th>Subject / Approach</th><th>Status</th><th>Requested</th><th>Completed</th></tr></thead><tbody>
        @forelse ($evaluations as $item)<tr><td>Classification</td><td>{{ ucfirst($item->subject) }}</td><td>{{ str($item->status)->replace('_',' ')->title() }}</td><td>{{ $item->requester?->name ?? 'System' }}</td><td>{{ $item->completed_at?->format('M j, Y g:i A') ?? 'Pending' }}</td></tr>@empty<tr><td colspan="5">No classification evaluations yet.</td></tr>@endforelse
        @foreach ($recommendationEvaluations->take(10) as $item)<tr><td>Recommendation</td><td>{{ str($item->approach)->replace('_',' ')->title() }}</td><td>{{ str($item->status)->replace('_',' ')->title() }}</td><td>{{ $item->requester?->name ?? 'System' }}</td><td>{{ $item->evaluated_at?->format('M j, Y g:i A') ?? 'Pending' }}</td></tr>@endforeach
    </tbody></table></div></section>
</main>
</div></div>
</body></html>
