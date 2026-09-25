<x-app-layout>
    @php
        $teacherName = auth()->user()->name;
        $teacherInitials = collect(explode(' ', $teacherName))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->join('');
    @endphp
    <div class="min-h-screen bg-surface">
        <x-teacher-sidebar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" active="assessments" />
        <main class="min-h-screen lg:ml-72">
            <x-teacher-topbar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" search-placeholder="Search assessments..." />
            <div class="mx-auto max-w-6xl p-5 sm:p-8">{{ $slot }}</div>
        </main>
    </div>
</x-app-layout>
