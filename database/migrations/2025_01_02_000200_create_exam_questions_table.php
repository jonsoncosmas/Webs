<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(1);
            $table->text('prompt');
            $table->unsignedSmallInteger('marks')->default(1);
            $table->text('answer_key')->nullable();
            $table->timestamps();

            $table->index(['exam_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
