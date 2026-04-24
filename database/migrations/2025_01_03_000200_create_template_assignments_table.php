<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->index();
            $table->foreignId('template_id')->index();
            $table->foreignId('created_by')->index();
            $table->foreignId('assigned_to_id')->nullable()->index();

            $table->string('class_label');
            $table->string('subject')->nullable();
            $table->string('term')->nullable();

            $table->enum('status', ['draft', 'ready', 'printed'])->default('draft')->index();

            $table->text('notes')->nullable();
            $table->json('data')->nullable();

            $table->timestamp('printed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_assignments');
    }
};
