<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlumniPasswordResetNotification extends Notification
{
    use Queueable;

    public function __construct(private string $resetUrl) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset Your SJUT Alumni Password')
            ->greeting('Dear ' . trim(($notifiable->f_name ?? '') . ' ' . ($notifiable->l_name ?? '')) . ',')
            ->line('An administrator has initiated a password reset for your SJUT Alumni account.')
            ->line('Click the button below to set a new password. This link expires in 48 hours.')
            ->action('Reset Password', $this->resetUrl)
            ->line('If you did not request this, you can ignore this email — your current password remains unchanged.')
            ->line('Thank you.');
    }
}
