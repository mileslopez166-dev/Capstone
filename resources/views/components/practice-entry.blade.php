<section class="practice-entry">
    <div><h2>Practice Missions</h2><span class="practice-muted">{{ \App\Models\PracticeMission::where('student_id', auth()->id())->where('status', 'assigned')->count() }} assigned &middot; {{ auth()->user()->practiceCoinBalance() }} practice coins</span></div>
    <a class="practice-button" href="{{ route('student.practice.index') }}"><span class="material-symbols-outlined" aria-hidden="true">flag</span> My practice</a>
    <a class="practice-link" href="{{ route('student.wardrobe.edit') }}"><span class="material-symbols-outlined" aria-hidden="true">checkroom</span> Wardrobe</a>
</section>
