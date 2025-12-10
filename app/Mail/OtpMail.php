<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $title;
    public $messageText;

    public function __construct($otp, $title = 'Your Verification Code')
    {
        $this->otp = $otp;
        $this->title = $title;
        $this->messageText = "Here is your one-time verification code for SJUT Mobile App.";
    }

   public function build()
{
    return $this->subject($this->title)
                ->view('emails.otp')
                ->with([
                    'otp'     => $this->otp,
                    'title'   => $this->title,
                    'bodyMessage' => $this->messageText,   
                ]);
}
}