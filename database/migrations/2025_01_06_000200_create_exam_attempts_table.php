<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->index();
            $table->foreignId('student_user_id')->index();
            $table->foreignId('school_id')->index();

            $table->unsignedSmallInteger('score')->nullable();
            $table->unsignedSmallInteger('total_marks')->nullable();
            $table->string('grade', 5)->nullable();

            $table->enum('status', ['scored', 'reviewed', 'archived'])->default('scored')->index();

            $table->foreignId('scored_by')->nullable()->index();
            $table->timestamp('scored_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['exam_id', 'student_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
