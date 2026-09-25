@props(['role'])
@php
    $accordionName = 'navigation-'.\Illuminate\Support\Str::uuid();
    $link = fn ($label, $href, $icon, $selected) => compact('label', 'href', 'icon', 'selected');
    if ($role === 'teacher') {
        $creating = request()->routeIs('assessments.create', 'worksheets.index', 'worksheets.create');
        $reviewing = request()->routeIs('worksheets.reviews', 'worksheets.review');
        $listing = ! $creating && ! $reviewing && request()->routeIs('assessments.*', 'teacher.assessments.*');
        $items = [
            $link('Dashboard', route('teacher.dashboard'), 'dashboard', request()->routeIs('teacher.dashboard', 'dashboard')),
            $link('Teachers AI Assistant', route('teacher.ai-assistant.index'), 'psychology', request()->routeIs('teacher.ai-assistant.*')),
            ['label' => 'Classroom', 'icon' => 'groups', 'key' => 'classroom', 'children' => [
                $link('Students', route('students.index'), 'group', request()->routeIs('students.*')),
                $link('Reports', route('reports.index'), 'assessment', request()->routeIs('reports.*', 'teacher.phil-iri.*')),
                $link('Practice', route('teacher.practice.index'), 'flag', request()->routeIs('teacher.practice.*')),
            ]],
            ['label' => 'Assessments', 'icon' => 'assignment', 'key' => 'assessments', 'children' => [
                $link('Create Assessment', route('assessments.create'), 'add', $creating),
                $link('Created Assessments', route('assessments.index'), 'inventory_2', $listing),
                $link('Worksheet Reviews', route('worksheets.reviews'), 'rate_review', $reviewing),
            ]],
            $link('Settings', route('profile.edit'), 'settings', request()->routeIs('profile.*')),
        ];
    } else {
        $routeAssessment = request()->route('assessment');
        $subject = request()->routeIs('student.activities') ? request()->query('subject')
            : ($routeAssessment instanceof \App\Models\Assessment ? $routeAssessment->subject : null);
        $activityPage = request()->routeIs('student.activities', 'student.assessments.*');
        $items = [
            $link('Dashboard', route('student.dashboard'), 'dashboard', request()->routeIs('student.dashboard')),
            ['label' => 'Activities', 'icon' => 'assignment', 'key' => 'activities', 'children' => [
                $link('All Activities', route('student.activities'), 'view_list', $activityPage && ! $subject),
                $link('Literacy', route('student.activities', ['subject' => 'literacy']), 'auto_stories', $activityPage && $subject === 'literacy'),
                $link('Numeracy', route('student.activities', ['subject' => 'numeracy']), 'calculate', $activityPage && $subject === 'numeracy'),
                $link('Worksheet Mission', route('worksheets.mission'), 'menu_book', request()->routeIs('worksheets.mission', 'worksheets.review')),
                $link('Practice Missions', route('student.practice.index'), 'flag', request()->routeIs('student.practice.*')),
                $link('Ask Tutor', route('student.tutor.index'), 'chat_bubble_outline', request()->routeIs('student.tutor.*')),
            ]],
            ['label' => 'Progress', 'icon' => 'insights', 'key' => 'progress', 'children' => [
                $link('Leaderboard', route('student.leaderboard'), 'leaderboard', request()->routeIs('student.leaderboard')),
                $link('Rewards', route('student.rewards'), 'backpack', request()->routeIs('student.rewards')),
            ]],
            ['label' => 'My Account', 'icon' => 'account_circle', 'key' => 'account', 'children' => [
                $link('Profile', route('profile.edit'), 'person', request()->routeIs('profile.*')),
                $link('Wardrobe', route('student.wardrobe.edit'), 'checkroom', request()->routeIs('student.wardrobe.*')),
            ]],
        ];
    }
@endphp
<div class="role-navigation" data-role-navigation="{{ $role }}">
    @foreach ($items as $item)
        @if (isset($item['children']))
            @php $selectedGroup = collect($item['children'])->contains('selected', true); @endphp
            <details class="navigation-group {{ $selectedGroup ? 'navigation-group-active' : '' }}" name="{{ $accordionName }}" data-navigation-group="{{ $item['key'] }}" @if ($selectedGroup) open @endif>
                <summary><span class="material-symbols-outlined" aria-hidden="true">{{ $item['icon'] }}</span><span>{{ $item['label'] }}</span><span class="material-symbols-outlined navigation-chevron" aria-hidden="true">expand_more</span></summary>
                <div class="navigation-children">
                    @foreach ($item['children'] as $child)
                        <a class="navigation-link" href="{{ $child['href'] }}" @if ($child['selected']) aria-current="page" @endif><span class="material-symbols-outlined" aria-hidden="true">{{ $child['icon'] }}</span><span>{{ $child['label'] }}</span></a>
                    @endforeach
                </div>
            </details>
        @else
            <a class="navigation-link" href="{{ $item['href'] }}" @if ($item['selected']) aria-current="page" @endif><span class="material-symbols-outlined" aria-hidden="true">{{ $item['icon'] }}</span><span>{{ $item['label'] }}</span></a>
        @endif
    @endforeach
</div>
