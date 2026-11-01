<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AcademicYearAnnouncementNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $recipientName,
        private readonly string $subjectLine,
        private readonly string $messageBody,
        private readonly string $academicYear,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subjectLine)
            ->greeting('Dear ' . $this->recipientName . ',')
            ->line($this->messageBody)
            ->line('Academic Year: ' . $this->academicYear)
            ->line('Please keep the SJUT App updated and check it regularly for official information.')
            ->salutation('Best wishes, St. John’s University of Tanzania');
    }
}
