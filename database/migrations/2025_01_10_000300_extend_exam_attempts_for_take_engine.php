<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->string('take_phase', 20)->nullable()->after('status');
            $table->timestamp('started_at')->nullable()->after('take_phase');
            $table->timestamp('submitted_at')->nullable()->after('started_at');
            $table->timestamp('deadline_at')->nullable()->after('submitted_at');
            $table->index('take_phase');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropIndex(['take_phase']);
            $table->dropColumn(['take_phase', 'started_at', 'submitted_at', 'deadline_at']);
        });
    }
};
