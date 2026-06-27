<?php

namespace App\Notifications;

use App\Models\Election;
use App\Models\PollingCentre;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PollingCentreLinkNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Election $election,
        public PollingCentre $centre,
        public string $link,
        public string $type = 'created'
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = $this->type === 'regenerated'
            ? 'Polling Centre Link Regenerated'
            : 'Polling Centre Link Created';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.polling-centre-link', [
                'election' => $this->election,
                'centre'  => $this->centre,
                'link'    => $this->link,
                'type'    => $this->type,
            ]);
    }
}