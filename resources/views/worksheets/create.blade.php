<x-worksheet-layout teacher :title="'Worksheet '.$worksheet['number']" :back="route('worksheets.index')">
    <form method="POST" action="{{ route('worksheets.store', $worksheet['number']) }}" class="worksheet-assignment">
        @csrf
        <div class="worksheet-fields">
            <label>Assessment title<input name="title" required maxlength="255" value="{{ old('title', $worksheet['title']) }}"></label>
            <label>Section<select name="target_section">@foreach (['all' => 'All sections', 'section_a' => 'Section A', 'section_b' => 'Section B', 'section_c' => 'Section C'] as $value => $label)<option value="{{ $value }}" @selected(old('target_section', strtolower(str_replace(' ', '_', auth()->user()->section ?? 'all'))) === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>Total scored items<input name="worksheet_total" type="number" min="1" max="1000" value="{{ old('worksheet_total') }}" required></label>
            <label>Retries after first attempt<select name="retry_limit">@foreach (\App\Models\Assessment::retryLimitOptions() as $value => $label)<option value="{{ $value }}" @selected((string) old('retry_limit', '0') === (string) $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="worksheet-wide">Teacher instructions<textarea name="instructions" maxlength="2000" rows="3">{{ old('instructions') }}</textarea></label>
        </div>
        <div class="worksheet-preview">
            @foreach ($worksheet['pages'] as $page)<section><h2>Part {{ $page['part'] }}</h2><x-worksheet-text :worksheet="$worksheet" :page="$page" /></section>@endforeach
        </div>
        <div class="worksheet-actions"><button class="ui-button ui-button-secondary" name="status" value="draft"><span class="material-symbols-outlined">lock</span>Save Locked</button><button class="ui-button" name="status" value="published"><span class="material-symbols-outlined">publish</span>Publish Worksheet</button></div>
    </form>
</x-worksheet-layout>
