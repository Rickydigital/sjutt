<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SemesterWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $student;

    public function __construct(Student $student)
    {
        $this->student = $student;
    }

    public function build()
    {
        return $this->subject('Welcome to the New Semester - St John\'s University of Tanzania')
                    ->view('emails.semester-welcome-student')
                    ->with([
                        'student' => $this->student,
                    ]);
    }
}