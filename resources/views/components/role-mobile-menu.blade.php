@props(['role'])
<details class="campus-mobile-menu role-mobile-menu lg:hidden" x-data="{}" @click.outside="$el.open = false" @keydown.escape.prevent.stop="$el.open = false; $el.querySelector('summary').focus()">
    <summary aria-label="{{ ucfirst($role) }} navigation" title="{{ ucfirst($role) }} navigation"><span class="material-symbols-outlined" aria-hidden="true">menu</span></summary>
    <nav aria-label="{{ ucfirst($role) }} mobile navigation">
        <x-role-navigation :role="$role" />
        <form class="navigation-mobile-logout" method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><span class="material-symbols-outlined" aria-hidden="true">logout</span>Logout</button></form>
    </nav>
</details>
