<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlumniTemporaryPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $temporaryPassword
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SJUT Alumni Account Created Successfully')
            ->greeting('Dear ' . trim(($notifiable->f_name ?? '') . ' ' . ($notifiable->l_name ?? '')) . ',')
            ->line('Your SJUT Alumni account was created successfully.')
            ->line('Please download the SJUT App from the official platform using the link below.')
            ->action('Download SJUT App', 'https://timetable.sjut.ac.tz/download-app/sjut-app-1.3.4.apk')
            ->line('After downloading the app, kindly login using your email and temporary password, then update your password.')
            ->line('Temporary password: ' . $this->temporaryPassword)
            ->line('Kindly note: this download link is mostly used for Android phones.')
            ->line('Thank you.');
    }
}