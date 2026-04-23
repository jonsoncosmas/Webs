<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index();
            $table->foreignId('school_id')->nullable()->index();
            $table->string('task_type')->index(); // report, explanation, data_analysis, generic
            $table->string('provider');           // claude, openai, gemini, deepseek
            $table->string('model')->nullable();
            $table->char('prompt_hash', 64)->nullable()->index();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending')->index();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_requests');
    }
};
