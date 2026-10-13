<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PhonePushNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $type,
        private readonly string $title,
        private readonly ?string $body,
        private readonly ?string $url,
    ) {
        $this->onQueue('default');
    }

    public static function isConfigured(): bool
    {
        return filled(config('webpush.vapid.public_key'))
            && filled(config('webpush.vapid.private_key'));
    }

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $message = (new WebPushMessage)
            ->title($this->title)
            ->icon(asset('favicon.svg'))
            ->badge(asset('favicon.svg'))
            ->tag($this->type)
            ->data([
                'type' => $this->type,
                'url' => $this->url ?? url('/'),
            ])
            ->options([
                'TTL' => 86400,
                'urgency' => 'normal',
            ]);

        if (filled($this->body)) {
            $message->body($this->body);
        }

        return $message;
    }
}
