<?php

namespace App\Notifications;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SemesterWelcomeStudentNotification extends Notification
{
    use Queueable;

    public $student;

    public function __construct(Student $student)
    {
        $this->student = $student;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];   // You can add 'database', 'fcm' later
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to the New Semester - St John\'s University')
            ->greeting("Dear {$this->student->first_name},")
            ->line('Welcome back to St John\'s University of Tanzania!')
            ->line('A new semester means new opportunities, new achievements, and new growth.')
            ->line('Stay focused, attend your classes on time, work hard, and believe in yourself.')
            ->line('We wish you a peaceful, productive, and successful semester.')
            ->line('— St John\'s University of Tanzania')
            ->salutation('Best Regards');
    }
}