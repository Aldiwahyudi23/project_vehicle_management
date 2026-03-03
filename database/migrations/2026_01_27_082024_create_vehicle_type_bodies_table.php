<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicle_type_bodies', function (Blueprint $table) {
            $table->id();
             $table->string('name');          // SUV, MPV, Hatchback
            $table->string('code')->unique(); // suv, mpv, hatchback
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            // 👉 UNIQUE gabungan brand_id + name
            $table->unique(['name']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_type_bodies');
    }
};
