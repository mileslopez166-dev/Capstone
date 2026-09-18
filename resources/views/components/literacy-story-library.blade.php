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
        <button id="load-story-template" class="ui-button disabled:cursor-not-allowed disabled:opacity-50" type="button" disabled>
            <span class="material-symbols-outlined" aria-hidden="true">library_books</span>Use Story
        </button>
        <span id="story-library-count" class="text-sm text-on-surface-variant"></span>
    </div>
    <p id="story-library-status" class="hidden text-sm font-medium text-secondary-dim" role="status" aria-live="polite"></p>
</div>
