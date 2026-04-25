<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_review_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->index();
            $table->foreignId('student_user_id')->index();
            $table->foreignId('school_id')->index();
            $table->foreignId('submitted_by')->index(); // student or parent

            $table->text('reason');
            $table->enum('status', ['pending', 'acknowledged', 'resolved', 'rejected'])->default('pending')->index();

            $table->foreignId('decided_by')->nullable()->index();
            $table->timestamp('decided_at')->nullable();
            $table->text('feedback')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_review_requests');
    }
};
