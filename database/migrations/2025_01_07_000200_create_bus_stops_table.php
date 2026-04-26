<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bus_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_route_id')->constrained('bus_routes')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('position')->default(0); // order along the route
            $table->time('scheduled_pickup')->nullable();
            $table->time('scheduled_dropoff')->nullable();
            $table->timestamps();

            $table->index(['bus_route_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_stops');
    }
};
