<?php

namespace App\Mail;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicationFollowUp extends Mailable
{
    use Queueable, SerializesModels;

    public $jobApplication;

    public function __construct(JobApplication $jobApplication)
    {
        $this->jobApplication = $jobApplication;
    }

    public function build()
    {
        return $this->subject('Your Job Application - Under Review')
                    ->view('emails.application-follow-up');
    }
}