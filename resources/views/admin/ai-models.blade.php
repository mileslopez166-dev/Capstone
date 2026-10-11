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

    <section class="space-y-6" aria-labelledby="classification-heading">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-xs font-bold uppercase tracking-widest text-primary">Section A</p><h2 id="classification-heading" class="font-headline text-2xl font-bold">Classification comparison</h2></div>
            <div class="flex flex-wrap gap-2">@foreach (['literacy' => 'Literacy', 'numeracy' => 'Numeracy'] as $subject => $label)<form method="POST" action="{{ route('admin.ai-models.classification.evaluate') }}">@csrf<input type="hidden" name="subject" value="{{ $subject }}"><button class="ui-button {{ $subject === 'literacy' ? 'ui-button-primary' : 'ui-button-secondary' }}" type="submit"><span class="material-symbols-outlined" aria-hidden="true">play_arrow</span>Run {{ $label }}</button></form>@endforeach</div>
        </div>
        @foreach (['literacy' => 'Literacy', 'numeracy' => 'Numeracy'] as $subject => $label)
            @php $evaluation=$latestBySubject->get($subject); $active=$activeConfigurations->get('classification:'.$subject); $versions=$evaluation?->versions ?? collect(); @endphp
            <div class="overflow-hidden rounded-md border border-outline-variant/20 bg-surface-container-lowest">
                <div class="flex flex-col gap-3 border-b border-outline-variant/15 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div><h3 class="font-headline text-xl font-bold">{{ $label }}</h3><p class="text-sm text-on-surface-variant">{{ data_get($evaluation?->dataset_info, 'rows', data_get($evaluation?->dataset_info, 'rows_sent', 0)) }} labeled records / {{ data_get($evaluation?->dataset_info, 'learners', 0) }} learners</p></div>
                    <div class="flex max-w-full flex-wrap gap-2 text-xs font-bold uppercase tracking-wide"><span class="rounded-full bg-surface-container-high px-3 py-1.5">{{ str($evaluation?->status ?? 'not_trained')->replace('_',' ')->title() }}</span><span class="max-w-full break-all rounded-full bg-primary-container/25 px-3 py-1.5 text-primary">{{ $active?->active_key ? 'Active: '.$active->active_key : 'No active comparison model' }}</span></div>
                </div>
                @if (data_get($evaluation?->dataset_info, 'class_distribution'))<p class="border-b border-outline-variant/15 px-5 py-3 text-xs text-on-surface-variant">Class distribution: {{ collect(data_get($evaluation->dataset_info, 'class_distribution'))->map(fn ($count, $level) => $level.' '.$count)->join(' / ') }}</p>@endif
                @if ($evaluation?->error_message)<p class="border-b border-outline-variant/15 px-5 py-3 text-sm text-on-surface-variant">{{ $evaluation->error_message }}</p>@endif
                <div class="grid gap-4 p-5 md:grid-cols-3">
                @foreach (['decision_tree'=>'Decision Tree','random_forest'=>'Random Forest','svm'=>'Support Vector Machine'] as $algorithm=>$name)
                    @php $version=$versions->firstWhere('algorithm',$algorithm); $metrics=$version?->metrics ?? []; @endphp
                    <article class="rounded-md border border-outline-variant/20 bg-surface p-4">
                        <div class="flex items-start justify-between gap-3"><h4 class="font-headline font-bold">{{ $name }}</h4><span class="text-[10px] font-bold uppercase tracking-wide">{{ str($version?->status ?? 'not_trained')->replace('_',' ')->title() }}</span></div>
                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">@foreach (['accuracy'=>'Accuracy','precision_macro'=>'Precision','recall_macro'=>'Recall','f1_macro'=>'F1'] as $key=>$metricLabel)<div><dt class="text-xs text-on-surface-variant">{{ $metricLabel }}</dt><dd class="mt-1 font-bold">{{ isset($metrics[$key]) ? number_format($metrics[$key]*100,1).'%' : 'Pending' }}</dd></div>@endforeach</dl>
                        @if (isset($metrics['f1_macro']))<div class="mt-4 h-2 overflow-hidden rounded-full bg-surface-container-high" aria-label="Macro F1 {{ number_format($metrics['f1_macro']*100,1) }} percent"><div class="h-full rounded-full bg-primary" style="width: {{ max(0, min(100, $metrics['f1_macro']*100)) }}%"></div></div>@endif
                        @if ($version)<p class="mt-3 break-all text-[11px] text-on-surface-variant">Version {{ $version->version }} / evaluated {{ $evaluation?->completed_at?->format('M j, Y') ?? 'pending' }}</p>@endif
                        @if ($evaluation?->recommended_algorithm===$algorithm)<p class="mt-4 text-xs font-bold text-secondary-dim">Recommended by validation macro F1</p>@endif
                        @if ($version?->status==='validated' && $active?->active_key!==$version->model_name)<form class="mt-4" method="POST" action="{{ route('admin.ai-models.classification.activate',$version) }}">@csrf<button class="ui-button ui-button-secondary w-full" type="submit">Activate</button></form>@elseif ($version && $active?->active_key===$version->model_name)<p class="mt-4 text-sm font-bold text-primary">Active model</p>@endif
                    </article>
                @endforeach
                </div>
                @if ($evaluation?->final_test_metrics)
                    @php $final=$evaluation->final_test_metrics; @endphp
                    <div class="grid gap-5 border-t border-outline-variant/15 p-5 lg:grid-cols-[1fr_auto]"><div><h4 class="font-bold">Final untouched holdout</h4><p class="mt-1 text-sm text-on-surface-variant">Used once after validation selected {{ str($final['algorithm'])->replace('_',' ')->title() }}. {{ $final['rows'] }} rows and {{ $final['learners'] }} learners.</p><div class="mt-3 flex flex-wrap gap-5"><p class="text-2xl font-bold text-primary">{{ number_format($final['f1_macro']*100,1) }}% <span class="text-sm font-medium text-on-surface-variant">macro F1</span></p><p class="text-2xl font-bold text-on-surface">{{ number_format($final['accuracy']*100,1) }}% <span class="text-sm font-medium text-on-surface-variant">accuracy</span></p><p class="text-2xl font-bold text-on-surface">{{ number_format($final['prediction_latency_ms_per_row'] ?? 0,2) }} <span class="text-sm font-medium text-on-surface-variant">ms / prediction</span></p></div></div><table class="text-center text-xs"><caption class="mb-2 font-bold">Confusion matrix</caption><tbody>@foreach ($final['confusion_matrix'] as $row)<tr>@foreach ($row as $cell)<td class="border border-outline-variant/20 px-3 py-2">{{ $cell }}</td>@endforeach</tr>@endforeach</tbody></table></div>
                    @if ($final['per_class'] ?? null)<div class="overflow-x-auto border-t border-outline-variant/15 p-5" role="region" aria-label="Per-class model metrics" tabindex="0"><table class="ui-data-table w-full"><thead><tr><th>Class</th><th>Precision</th><th>Recall</th><th>F1</th><th>Support</th></tr></thead><tbody>@foreach ($final['per_class'] as $level=>$classMetrics)<tr><td>{{ $level }}</td><td>{{ number_format($classMetrics['precision']*100,1) }}%</td><td>{{ number_format($classMetrics['recall']*100,1) }}%</td><td>{{ number_format($classMetrics['f1']*100,1) }}%</td><td>{{ $classMetrics['support'] }}</td></tr>@endforeach</tbody></table></div>@endif
                @endif
                @if ($active?->previous_key)<form class="border-t border-outline-variant/15 p-4 text-right" method="POST" action="{{ route('admin.ai-models.rollback') }}">@csrf<input type="hidden" name="type" value="classification"><input type="hidden" name="subject" value="{{ $subject }}"><button class="text-sm font-bold text-primary" type="submit">Restore previous model</button></form>@endif
            </div>
        @endforeach
    </section>
    <section class="space-y-5 border-t border-outline-variant/20 pt-8" aria-labelledby="recommendation-heading">
        <div><p class="text-xs font-bold uppercase tracking-widest text-primary">Section B</p><h2 id="recommendation-heading" class="font-headline text-2xl font-bold">Recommendation comparison</h2></div>
        <div class="grid gap-4 md:grid-cols-3">
        @foreach (['content_based'=>['Content-Based',true],'collaborative'=>['Collaborative',false],'hybrid'=>['Hybrid',true]] as $approach=>[$name,$coldStart])
            @php $rec=$latestRecommendations->get($approach); $metrics=$rec?->metrics ?? []; $activeRec=$activeConfigurations->get('recommendation:all'); @endphp
            <article class="rounded-md border border-outline-variant/20 bg-surface-container-lowest p-5">
                <div class="flex justify-between gap-3"><h3 class="font-headline text-lg font-bold">{{ $name }}</h3><span class="text-[10px] font-bold uppercase">{{ str($rec?->status ?? 'not_evaluated')->replace('_',' ')->title() }}</span></div>
                <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">@foreach (['precision_at_5'=>'Precision@5','recall_at_5'=>'Recall@5','ndcg_at_5'=>'NDCG@5','coverage'=>'Coverage'] as $key=>$metricLabel)<div><dt class="text-xs text-on-surface-variant">{{ $metricLabel }}</dt><dd class="mt-1 font-bold">{{ isset($metrics[$key]) ? number_format($metrics[$key]*100,1).'%' : 'Pending' }}</dd></div>@endforeach</dl>
                @if (isset($metrics['ndcg_at_5']))<div class="mt-4 h-2 overflow-hidden rounded-full bg-surface-container-high" aria-label="NDCG at 5 {{ number_format($metrics['ndcg_at_5']*100,1) }} percent"><div class="h-full rounded-full bg-secondary" style="width: {{ max(0, min(100, $metrics['ndcg_at_5']*100)) }}%"></div></div>@endif
                <p class="mt-3 text-xs text-on-surface-variant">Response time: <strong>{{ isset($metrics['response_time_ms']) ? number_format($metrics['response_time_ms'],2).' ms' : 'Pending' }}</strong></p>
                @if (isset($metrics['fallback_rate']))<p class="mt-1 text-xs text-on-surface-variant">Content fallback: <strong>{{ number_format($metrics['fallback_rate']*100,1) }}%</strong></p>@endif
                <p class="mt-4 text-xs text-on-surface-variant">Cold start: <strong>{{ $coldStart ? 'Supported' : 'Requires interaction history' }}</strong></p>
                @if ($rec?->notes)<p class="mt-2 text-xs text-on-surface-variant">{{ $rec->notes }}</p>@endif
                @if ($rec?->status==='evaluated' && !str_starts_with($activeRec?->active_key ?? '',$approach))
                    <form class="mt-4" method="POST" action="{{ route('admin.ai-models.recommendations.activate') }}">@csrf<input type="hidden" name="approach" value="{{ $approach }}">@if ($approach==='hybrid')<input type="hidden" name="alpha" value="{{ data_get($metrics,'alpha',.7) }}">@endif<button class="ui-button ui-button-secondary w-full" type="submit">Activate</button></form>
                @elseif (str_starts_with($activeRec?->active_key ?? '',$approach))<p class="mt-4 text-sm font-bold text-primary">Active approach</p>@endif
            </article>
        @endforeach
        </div>
        @php $activeRec=$activeConfigurations->get('recommendation:all'); @endphp
        @if ($activeRec?->previous_key)<form class="text-right" method="POST" action="{{ route('admin.ai-models.rollback') }}">@csrf<input type="hidden" name="type" value="recommendation"><input type="hidden" name="subject" value="all"><button class="text-sm font-bold text-primary" type="submit">Restore previous recommendation approach</button></form>@endif
    </section>
    <section class="border-t border-outline-variant/20 pt-8"><h2 class="font-headline text-2xl font-bold">Recent learning recommendations</h2><div class="mt-4 overflow-x-auto" role="region" aria-label="Recent generated learning recommendations" tabindex="0"><table class="ui-data-table w-full"><thead><tr><th>Learner</th><th>Activity</th><th>Approach</th><th>Reason</th><th>Status</th></tr></thead><tbody>
        @forelse ($recentLearningRecommendations as $item)<tr><td>Student_{{ str_pad((string) $item->student_id, 3, '0', STR_PAD_LEFT) }}</td><td>{{ $item->assessment?->title ?? 'Removed activity' }}</td><td>{{ str($item->approach)->replace('_',' ')->title() }}</td><td>{{ collect($item->reasons ?? [])->take(2)->join(' / ') ?: 'No reason recorded' }}</td><td>{{ str($item->status)->title() }}</td></tr>@empty<tr><td colspan="5">No recommendations have been generated yet.</td></tr>@endforelse
    </tbody></table></div></section>
    <section class="border-t border-outline-variant/20 pt-8"><h2 class="font-headline text-2xl font-bold">Evaluation history</h2><div class="mt-4 overflow-x-auto"><table class="ui-data-table w-full"><thead><tr><th>Type</th><th>Subject / Approach</th><th>Status</th><th>Requested</th><th>Completed</th></tr></thead><tbody>
        @forelse ($evaluations as $item)<tr><td>Classification</td><td>{{ ucfirst($item->subject) }}</td><td>{{ str($item->status)->replace('_',' ')->title() }}</td><td>{{ $item->requester?->name ?? 'System' }}</td><td>{{ $item->completed_at?->format('M j, Y g:i A') ?? 'Pending' }}</td></tr>@empty<tr><td colspan="5">No classification evaluations yet.</td></tr>@endforelse
        @foreach ($recommendationEvaluations->take(10) as $item)<tr><td>Recommendation</td><td>{{ str($item->approach)->replace('_',' ')->title() }}</td><td>{{ str($item->status)->replace('_',' ')->title() }}</td><td>{{ $item->requester?->name ?? 'System' }}</td><td>{{ $item->evaluated_at?->format('M j, Y g:i A') ?? 'Pending' }}</td></tr>@endforeach
    </tbody></table></div></section>
</main>
</div></div>
</body></html>
