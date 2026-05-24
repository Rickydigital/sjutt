<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SemesterWelcomeLecturerNotification extends Notification
{
    use Queueable;

    public $lecturer;

    public function __construct(User $lecturer)
    {
        $this->lecturer = $lecturer;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to the New Academic Semester - St John\'s University')
            ->greeting("Dear {$this->lecturer->name},")
            ->line('Welcome to the new academic semester at St John\'s University of Tanzania.')
            ->line('Thank you for your continued dedication, mentorship, and contribution to academic excellence.')
            ->line('We wish you a productive, peaceful, and highly successful semester.')
            ->line('— St John\'s University of Tanzania')
            ->salutation('Best Regards');
    }
}