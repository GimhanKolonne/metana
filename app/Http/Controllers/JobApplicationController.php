<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JobApplicationController extends Controller
{
    public function index()
    {
        return view('job-application.form');
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone' => 'required|string|max:20',
        'cv' => 'required|file|mimes:pdf,docx|max:10240',
        'is_production' => 'boolean',
    ]);

    // to upload the cv to s3 storage
    $path = $request->file('cv')->store('cvs', 's3');
    

    // to generate a public link for the cv 
    $publicLink = Storage::disk('s3')->url($path);

    // to create job application record
    $jobApplication = JobApplication::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'phone' => $validated['phone'],
        'cv_path' => $path,
        'cv_public_link' => $publicLink,
        'status' => $request->has('is_production') && $request->is_production ? 'prod' : 'testing',
    ]);

    // add the cv processing job to queue
    dispatch(new \App\Jobs\ProcessCV($jobApplication));

    return redirect()->route('job-application.success')->with('message', 'Your application has been submitted successfully!');
}
    public function success()
    {
        return view('job-application.success');
    }
}