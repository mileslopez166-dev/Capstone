@props(['active' => 'overview', 'adminUser', 'adminInitials'])

<aside class="admin-sidebar" x-data="{ navOpen: false }" @keydown.escape.window="navOpen = false" @click.outside="navOpen = false">
    <a class="admin-brand" href="{{ route('admin.dashboard') }}">
        <x-application-logo class="h-8 w-8" />
        <span>AI-PGAALS<small>Administration</small></span>
    </a>
    <button class="admin-menu-toggle" type="button" @click="navOpen = !navOpen" :aria-expanded="navOpen" aria-controls="admin-navigation" aria-label="Admin navigation" title="Admin navigation">
        <span class="material-symbols-outlined" x-text="navOpen ? 'close' : 'menu'" aria-hidden="true">menu</span>
    </button>
    <div id="admin-navigation" class="admin-navigation" :class="{ 'is-open': navOpen }">
        <p class="admin-nav-label">Workspace</p>
        <nav class="admin-nav-links" aria-label="Admin navigation">
            @foreach ([
                ['key' => 'overview', 'id' => 'admin-overview-nav', 'label' => 'Overview', 'icon' => 'space_dashboard', 'url' => route('admin.dashboard')],
                ['key' => 'users', 'id' => 'admin-users-nav', 'label' => 'User Management', 'icon' => 'group', 'url' => route('admin.dashboard').'#user-management'],
                ['key' => 'tokens', 'id' => 'admin-tokens-nav', 'label' => 'Token Requests', 'icon' => 'confirmation_number', 'url' => route('admin.token-requests.index')],
                ['key' => 'trash', 'id' => 'admin-trash-nav', 'label' => 'Trash', 'icon' => 'delete', 'url' => route('admin.users.trash')],
            ] as $link)
                <a id="{{ $link['id'] }}" class="{{ $active === $link['key'] ? 'bg-surface-container-high' : '' }}" href="{{ $link['url'] }}" @click="navOpen = false" @if ($active === $link['key']) aria-current="page" @endif>
                    <span class="material-symbols-outlined" aria-hidden="true">{{ $link['icon'] }}</span>
                    <span>{{ $link['label'] }}</span>
                </a>
            @endforeach
        </nav>
        <div class="admin-nav-bottom">
            <a href="{{ route('support.developing') }}"><span class="material-symbols-outlined" aria-hidden="true">help</span>Support</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"><span class="material-symbols-outlined" aria-hidden="true">logout</span>Sign Out</button>
            </form>
            <div class="admin-identity">
                <span>{{ $adminInitials }}</span>
                <div><strong title="{{ $adminUser->name }}">{{ $adminUser->name }}</strong><small>System Administrator</small></div>
                <span class="material-symbols-outlined" title="Protected account" aria-label="Protected account">verified_user</span>
            </div>
        </div>
    </div>
</aside>
