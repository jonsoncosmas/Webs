<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discipline_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->index();
            $table->foreignId('subject_id')->index();           // who the incident is about
            $table->string('subject_role', 60)->nullable();     // snapshotted role slug at time of log
            $table->foreignId('reported_by')->index();
            $table->foreignId('decided_by')->nullable()->index();

            $table->enum('category', [
                'minor',
                'major',
                'warning',
                'suspension',
                'commendation',
                'teacher_conduct',
            ])->index();

            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->date('occurred_on')->index();

            // 1 (trivial) → 5 (critical)
            $table->unsignedTinyInteger('severity')->default(2);

            $table->enum('status', ['open', 'resolved', 'dismissed'])->default('open')->index();
            $table->text('resolution')->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discipline_incidents');
    }
};
