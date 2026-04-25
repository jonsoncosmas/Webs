<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_student_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_user_id')->index();
            $table->foreignId('student_user_id')->index();
            $table->foreignId('school_id')->index();
            $table->string('relationship', 40)->nullable(); // father, mother, guardian, other
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['parent_user_id', 'student_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_student_links');
    }
};
