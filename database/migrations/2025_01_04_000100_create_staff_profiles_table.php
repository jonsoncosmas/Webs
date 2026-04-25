<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->foreignId('school_id')->index();

            $table->string('employee_no')->nullable()->index();
            $table->enum('employment_type', ['permanent', 'contract', 'part_time', 'intern', 'casual'])->nullable();
            $table->date('hired_on')->nullable();
            $table->date('contract_ends_on')->nullable();

            // National identifiers
            $table->string('nhif_number')->nullable();
            $table->string('nssf_number')->nullable();
            $table->string('tin_number')->nullable();
            $table->string('national_id')->nullable();

            // Banking
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_branch')->nullable();

            // Next of kin / emergency
            $table->string('next_of_kin_name')->nullable();
            $table->string('next_of_kin_relation')->nullable();
            $table->string('next_of_kin_phone')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();

            // Personal
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('marital_status', 20)->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('updated_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
    }
};
