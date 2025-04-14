<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewMessageNotification extends Notification
{
    use Queueable;

    protected $suggestion;

    public function __construct($suggestion)
    {
        $this->suggestion = $suggestion;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sender = $this->suggestion->is_anonymous ? 'Anonymous' : ($this->suggestion->student->name ?? 'Unknown');
        return (new MailMessage)
            ->subject('New Message in SJUT App')
            ->line('A new message has been received.')
            ->line("**Sender**: $sender")
            ->line("**Message**: {$this->suggestion->message}")
            ->action('View Messages', url('/admin/suggestions'))
            ->line('Please review the message in the admin panel.');
    }

    public function toArray($notifiable): array
    {
        return [
            'suggestion_id' => $this->suggestion->id,
            'message' => $this->suggestion->message,
            'sender_type' => $this->suggestion->sender_type ?? 'student',
        ];
    }
}