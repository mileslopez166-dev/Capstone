@props(['teacher' => false, 'title', 'back' => null])
<x-app-layout>
    <div x-data="{ mobileMenuOpen: false }">
        @if ($teacher)
            @php
                $teacherName = auth()->user()->name;
                $teacherInitials = collect(explode(' ', $teacherName))->take(2)->map(fn ($part) => substr($part, 0, 1))->join('');
            @endphp
            <div class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full lg:translate-x-0" :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'">
                <x-teacher-sidebar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" active="assessments" />
            </div>
            <button x-show="mobileMenuOpen" @click="mobileMenuOpen = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden" aria-label="Close menu"></button>
            <div class="lg:ml-72"><x-teacher-topbar :teacher-name="$teacherName" :teacher-initials="$teacherInitials"><x-slot:mobileTrigger><button class="lg:hidden" @click="mobileMenuOpen = true" aria-label="Open menu"><span class="material-symbols-outlined">menu</span></button></x-slot:mobileTrigger></x-teacher-topbar></div>
        @else
            <x-student-nav active="activities" />
        @endif
        <main class="worksheet-page lg:ml-72">
            <header class="worksheet-heading">
                @if ($back)<a href="{{ $back }}" class="worksheet-icon" title="Back to assessments" aria-label="Back to assessments"><span class="material-symbols-outlined">arrow_back</span></a>@endif
                <div><p>NUMERACY / GRADE 6</p><h1>{{ $title }}</h1></div>
            </header>
            @if (session('status'))<p class="worksheet-notice" role="status">{{ session('status') }}</p>@endif
            @if ($errors->any())<div class="worksheet-error" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            {{ $slot }}
            <footer class="worksheet-attribution">ARAL Mathematics. Department of Education. Includes iSipnayan materials by OSSFF. <a href="{{ route('worksheets.attribution') }}" target="_blank" rel="noopener">Source attribution</a></footer>
        </main>
    </div>
</x-app-layout>
