<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app = config('app.name', 'Shore Reporting');

        return (new MailMessage)
            ->subject(__('notifications.password_changed_subject', ['app' => $app]))
            ->greeting(__('notifications.password_changed_greeting', ['name' => $notifiable->full_name]))
            ->line(__('notifications.password_changed_line', ['app' => $app]))
            ->line(__('notifications.password_changed_unknown'))
            ->action(__('notifications.password_changed_action'), url('/login'));
    }
}
