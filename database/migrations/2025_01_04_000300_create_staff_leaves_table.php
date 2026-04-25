<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->foreignId('school_id')->index();

            $table->enum('type', ['annual', 'sick', 'maternity', 'paternity', 'compassionate', 'study', 'unpaid', 'other']);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->text('reason')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending')->index();
            $table->text('decision_comment')->nullable();

            $table->foreignId('requested_by')->index();
            $table->foreignId('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_leaves');
    }
};
