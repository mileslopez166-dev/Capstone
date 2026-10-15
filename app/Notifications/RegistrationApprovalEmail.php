<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationApprovalEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly User $registeredUser)
    {
        $this->onQueue('default');
    }

    public static function isConfigured(): bool
    {
        return filter_var(config('admin-notifications.registration_email'), FILTER_VALIDATE_EMAIL) !== false;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $role = ucfirst($this->registeredUser->role);
        $section = filled($this->registeredUser->section) ? $this->registeredUser->section : 'Not assigned';

        return (new MailMessage)
            ->subject('New AI-PGAALS registration needs approval')
            ->greeting('New account registration')
            ->line('A new account is waiting for administrator approval.')
            ->line('Name: '.$this->registeredUser->name)
            ->line('Email: '.$this->registeredUser->email)
            ->line('Role: '.$role)
            ->line('Section: '.$section)
            ->action('Review registration', route('admin.token-requests.index'))
            ->line('The account cannot sign in until it is approved.');
    }
}
