<?php

namespace App\Jobs;

use App\Models\JobApplication;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendFollowUpEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobApplication;

    public function __construct(JobApplication $jobApplication)
    {
        $this->jobApplication = $jobApplication;
    }

    public function handle()
    {
        // send follow-up email
        Mail::to($this->jobApplication->email)
            ->send(new \App\Mail\ApplicationFollowUp($this->jobApplication));
        
        // update the record to sent
        $this->jobApplication->update([
            'email_sent' => true
        ]);
    }
}