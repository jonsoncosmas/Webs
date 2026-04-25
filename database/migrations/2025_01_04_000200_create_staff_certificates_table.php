<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->foreignId('school_id')->index();

            $table->string('title');
            $table->string('issuer')->nullable();
            $table->string('reference_no')->nullable();
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable()->index();
            $table->string('document_url')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true)->index();

            $table->foreignId('added_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_certificates');
    }
};
