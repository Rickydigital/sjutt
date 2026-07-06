<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlumniTemporaryPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $temporaryPassword
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SJUT Alumni Account Created')
            ->view('emails.alumni-temporary-password', [
                'alumnus' => $notifiable,
                'temporaryPassword' => $this->temporaryPassword,
            ]);
    }
}
