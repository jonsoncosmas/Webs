<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('actor_id')->index();
            $table->enum('action', [
                'created',
                'submitted',
                'approved',
                'rejected',
                'overridden',
                'published',
                'archived',
                'edited',
            ])->index();
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('comment')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_events');
    }
};
