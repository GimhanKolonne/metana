<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'cv_path',
        'cv_public_link',
        'personal_info',
        'education',
        'qualifications',
        'projects',
        'cv_processed',
        'status',
        'processed_timestamp',
        'email_sent',
    ];

    protected $casts = [
        'personal_info' => 'array',
        'education' => 'array',
        'qualifications' => 'array',
        'projects' => 'array',
        'cv_processed' => 'boolean',
        'email_sent' => 'boolean',
        'processed_timestamp' => 'datetime',
    ];
}