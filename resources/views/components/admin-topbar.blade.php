@props(['title' => 'Overview', 'adminUser', 'adminInitials', 'searchSuggestions' => [], 'searchAction' => null, 'searchName' => 'q', 'searchValue' => null, 'searchPlaceholder' => 'Search users or token requests...'])

<header class="admin-topbar">
    <div class="admin-breadcrumb"><span>Admin Console</span><span class="material-symbols-outlined" aria-hidden="true">chevron_right</span><strong>{{ $title }}</strong></div>
    <form class="admin-search" method="GET" action="{{ $searchAction ?? route('admin.search') }}">
        <button type="submit" aria-label="Search admin workspace" title="Search"><span class="material-symbols-outlined" aria-hidden="true">search</span></button>
        <input type="search" name="{{ $searchName }}" value="{{ $searchValue ?? request('q', request('search')) }}" placeholder="{{ $searchPlaceholder }}" aria-label="{{ $searchPlaceholder }}" list="admin-search-suggestions">
        <datalist id="admin-search-suggestions">
            @foreach ($searchSuggestions as $suggestion)
                <option value="{{ $suggestion }}"></option>
            @endforeach
        </datalist>
    </form>
    <div class="admin-topbar-actions">
        <x-notification-menu :user="$adminUser" />
        <a class="admin-account" href="{{ route('profile.edit') }}" aria-label="Administrator profile" title="Administrator profile">{{ $adminInitials }}</a>
    </div>
</header>
