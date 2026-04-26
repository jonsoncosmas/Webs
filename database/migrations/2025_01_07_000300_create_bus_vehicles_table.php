<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bus_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bus_route_id')->nullable()->constrained('bus_routes')->nullOnDelete();
            $table->foreignId('driver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('plate_number', 32);
            $table->string('label')->nullable();
            $table->unsignedInteger('capacity')->default(0);
            $table->enum('status', ['active', 'maintenance', 'archived'])->default('active')->index();
            // Last-known position; will be replaced by streamed GPS in a future PR.
            $table->decimal('last_latitude', 10, 7)->nullable();
            $table->decimal('last_longitude', 10, 7)->nullable();
            $table->timestamp('last_position_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'plate_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_vehicles');
    }
};
