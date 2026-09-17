@props(['teacher' => false, 'title', 'back' => null])
<x-app-layout>
    @if ($teacher)
        @php
            $teacherName = auth()->user()->name;
            $teacherInitials = collect(explode(' ', $teacherName))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
        @endphp
        <x-teacher-sidebar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" active="practice" />
        <div class="lg:ml-72"><x-teacher-topbar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" /></div>
    @else
        <x-student-nav active="practice" />
    @endif
    <main class="practice-page lg:ml-72 {{ $teacher ? 'practice-teacher' : 'practice-student' }}">
        <div class="practice-content">
            <header class="practice-heading">
                @if ($back)<a class="practice-back" href="{{ $back }}"><span class="material-symbols-outlined" aria-hidden="true">arrow_back</span> Practice missions</a>@endif
                <h1>{{ $title }}</h1>
            </header>
            @if (session('practice_status'))<div class="practice-notice" role="status">{{ session('practice_status') }}</div>@endif
            @if ($errors->any())
                <div class="practice-errors" role="alert"><strong>Please check your mission.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            {{ $slot }}
        </div>
    </main>
</x-app-layout>
