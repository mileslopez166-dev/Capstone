@props(['stories'])
<div id="story-library" class="space-y-3 border-y border-outline-variant/20 py-4">
    <label class="block text-sm font-bold text-on-surface" for="story-library-select">Default story</label>
    <div class="flex flex-wrap items-center gap-3">
        <select id="story-library-select" class="min-w-0 w-full rounded border-outline-variant/20 bg-white px-3 py-3 text-sm">
            <option value="">Choose a story</option>
            @foreach ($stories as $story)
                <option value="{{ $story['id'] }}">{{ $story['story_title'] }}</option>
            @endforeach
        </select>
        <label class="block text-sm font-bold text-on-surface" for="story-library-level">Question selection</label>
        <select id="story-library-level" class="w-full rounded border-outline-variant/20 bg-white px-3 py-3 text-sm" disabled>
            <option value="">Choose a story first</option>
        </select>
        <button id="load-story-template" class="ui-button disabled:cursor-not-allowed disabled:opacity-50" type="button" disabled>
            <span class="material-symbols-outlined" aria-hidden="true">library_books</span>Use Story
        </button>
        <span id="story-library-count" class="text-sm text-on-surface-variant"></span>
    </div>
    <p id="story-library-status" class="hidden text-sm font-medium text-secondary-dim" role="status" aria-live="polite"></p>
    <details id="story-library-guide" class="hidden border-t border-outline-variant/20 pt-3 text-sm text-on-surface">
        <summary class="cursor-pointer py-2 font-bold">Template answer guide</summary>
        <ol id="story-library-guide-items" class="list-decimal space-y-3 pl-6"></ol>
    </details>
    <label class="block text-sm font-bold text-on-surface" for="question-selection">Loaded question delivery</label>
    <select id="question-selection" name="question_selection" class="w-full rounded border-outline-variant/20 bg-white px-3 py-3 text-sm">
        <option value="fixed" @selected(old('question_selection', 'fixed') === 'fixed')>Fixed (all questions)</option>
        <option value="automatic" @selected(old('question_selection') === 'automatic')>Automatic (8 questions)</option>
    </select>
    @error('question_selection')<p class="text-sm text-error" role="alert">{{ $message }}</p>@enderror
</div>
