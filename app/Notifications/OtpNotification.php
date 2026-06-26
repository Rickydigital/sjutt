<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OtpNotification extends Notification
{
    use Queueable;

    protected string $otp;
    protected string $title;
    protected string $messageText;

    public function __construct(
        string $otp,
        string $title = 'Your Verification Code'
    ) {
        $this->otp = $otp;
        $this->title = $title;
        $this->messageText = 'Here is your one-time verification code for SJUT Mobile App.';
    }

    /**
     * Notification channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Mail notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->title)
            ->view('emails.otp', [
                'otp'         => $this->otp,
                'title'       => $this->title,
                'bodyMessage' => $this->messageText,
            ]);
    }
}