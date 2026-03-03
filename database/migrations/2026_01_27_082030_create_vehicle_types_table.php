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
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('vehicle_models')->onDelete('cascade');
            $table->string('name');

              // Relasi ke type_bodies
            $table->foreignId('type_body_id')
                ->constrained('vehicle_type_bodies')
                ->onDelete('cascade');

            $table->boolean('is_active')->default(true);
                
            $table->timestamps();

            // 👉 UNIQUE gabungan model_id + name
            $table->unique(['model_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
    }
};
