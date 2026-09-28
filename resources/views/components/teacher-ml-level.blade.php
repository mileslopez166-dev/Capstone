@props(['student'])
<div class="teacher-ml-level" data-teacher-ml-level data-url="{{ route('students.ml-level', $student) }}" aria-busy="true">
    <strong data-overall-level role="status">Loading ML estimate...</strong>
    <p data-overall-score></p>
    <p data-overall-confidence></p>
    <small data-overall-basis></small>
    <button type="button" class="ui-button ui-button-secondary" data-overall-retry hidden>
        <span class="material-symbols-outlined" aria-hidden="true">refresh</span>Retry
    </button>
</div>
