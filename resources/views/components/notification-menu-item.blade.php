<div class="flex gap-3">
    <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $isUnread ? 'bg-primary' : 'bg-outline-variant' }}"></span>
    <div class="min-w-0">
        <p class="text-sm font-bold text-on-surface">{{ $notification->title }}</p>
        @if ($notification->body)
            <p class="mt-1 line-clamp-2 text-xs leading-5 text-on-surface-variant">{{ $notification->body }}</p>
        @endif
        <p class="mt-1 text-[11px] font-semibold uppercase tracking-wider text-outline">{{ $notification->created_at?->diffForHumans() }}</p>
    </div>
</div>