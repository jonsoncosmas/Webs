<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->index();
            $table->foreignId('creator_id')->index();
            $table->unsignedSmallInteger('creator_role_level'); // snapshot at creation
            $table->foreignId('locked_to_id')->nullable()->index(); // Director-locked exams
            $table->foreignId('approver_id')->nullable()->index();

            $table->string('title');
            $table->string('subject');
            $table->string('form_level')->nullable();
            $table->string('curriculum')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedSmallInteger('total_marks')->nullable();
            $table->timestamp('scheduled_at')->nullable();

            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'rejected',
                'published',
                'archived',
            ])->default('draft')->index();

            $table->text('notes')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
