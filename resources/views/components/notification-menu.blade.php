@props([
    'user' => null,
])

@php
    $notificationUser = $user ?? Auth::user();
    $notifications = $notificationUser
        ? $notificationUser->appNotifications()->latest()->take(8)->get()
        : collect();
    $unreadCount = $notificationUser
        ? $notificationUser->appNotifications()->unread()->count()
        : 0;
@endphp

<details class="group relative">
    <summary class="relative flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-full text-slate-500 transition-colors hover:bg-surface-container-low hover:text-primary [&::-webkit-details-marker]:hidden">
        <span class="material-symbols-outlined">notifications</span>
        @if ($unreadCount > 0)
            <span class="absolute right-1 top-1 flex min-h-4 min-w-4 items-center justify-center rounded-full bg-error px-1 text-[10px] font-bold leading-none text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </summary>

    <div class="absolute right-0 z-50 mt-3 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-DEFAULT border border-surface-variant/50 bg-white text-left shadow-[0_24px_70px_rgba(15,23,42,0.18)]">
        <div class="flex items-center justify-between border-b border-surface-variant/40 px-4 py-3">
            <div>
                <p class="font-headline text-sm font-bold text-on-surface">Notifications</p>
                <p class="text-xs text-on-surface-variant">{{ $unreadCount }} unread</p>
            </div>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.mark-read') }}">
                    @csrf
                    <button class="rounded-full bg-surface-container-low px-3 py-1 text-xs font-bold text-primary transition-colors hover:bg-primary hover:text-white" type="submit">
                        Mark read
                    </button>
                </form>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto py-2">
            @forelse ($notifications as $notification)
                @php
                    $isUnread = is_null($notification->read_at);
                    $content = view('components.notification-menu-item', compact('notification', 'isUnread'))->render();
                @endphp

                @if ($notification->url)
                    <a class="block px-4 py-3 transition-colors hover:bg-surface-container-low {{ $isUnread ? 'bg-primary-container/10' : '' }}" href="{{ $notification->url }}">
                        {!! $content !!}
                    </a>
                @else
                    <div class="px-4 py-3 {{ $isUnread ? 'bg-primary-container/10' : '' }}">
                        {!! $content !!}
                    </div>
                @endif
            @empty
                <div class="px-4 py-8 text-center">
                    <span class="material-symbols-outlined text-3xl text-outline">notifications_off</span>
                    <p class="mt-2 text-sm font-semibold text-on-surface">No notifications yet</p>
                    <p class="text-xs text-on-surface-variant">New activity will appear here.</p>
                </div>
            @endforelse
        </div>
    </div>
</details>