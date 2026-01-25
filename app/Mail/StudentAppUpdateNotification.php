<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;
use App\Models\Student;

class StudentAppUpdateNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $student;
    public $downloadLink = 'https://timetable.sjut.ac.tz/download-app/sjut-app-1.1.0.apk';

    /**
     * Create a new message instance.
     */
    public function __construct(Student $student)
    {
        $this->student = $student;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'St. John\'s University of Tanzania'),
            subject: '📱 SJUT Mobile App Updated! New Features & Latest Version Available',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.student-app-update', // We'll create this Blade view next
            with: [
                'student' => $this->student,
                'downloadLink' => $this->downloadLink,
                'fullName' => trim("{$this->student->first_name} {$this->student->last_name}"),
                'greeting' => trim("{$this->student->first_name} {$this->student->last_name}")
                    ? "Dear {$this->student->first_name} {$this->student->last_name},"
                    : "Dear Student,",
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}