<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_question_id')->constrained('exam_questions')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(1);
            $table->text('text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->index(['exam_question_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};
