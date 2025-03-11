<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('cv_path');
            $table->string('cv_public_link');
            $table->json('personal_info')->nullable();
            $table->json('education')->nullable();
            $table->json('qualifications')->nullable();
            $table->json('projects')->nullable();
            $table->boolean('cv_processed')->default(false);
            $table->string('status')->default('testing');
            $table->timestamp('processed_timestamp')->nullable();
            $table->boolean('email_sent')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_applications');
    }
};