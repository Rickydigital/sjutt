<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LecturerSemesterWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $lecturer;

    public function __construct(User $lecturer)
    {
        $this->lecturer = $lecturer;
    }

    public function build()
    {
        return $this->subject('Welcome to the New Academic Semester - St John\'s University of Tanzania')
                    ->view('emails.semester-welcome-lecturer')
                    ->with([
                        'lecturer' => $this->lecturer,
                    ]);
    }
}