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
        Schema::create('vehicle_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('vehicle_brands');
            $table->foreignId('model_id')->constrained('vehicle_models');
            $table->foreignId('type_id')->constrained('vehicle_types');
            $table->integer('year');
            $table->integer('cc');
            $table->string('fuel_type');
            $table->foreignId('transmission_id')->constrained('transmission_types');
            $table->string('engine_type')->nullable();
            $table->foreignId('origin_id')->constrained('vehicle_origins');
            $table->string('generation')->nullable();
            $table->string('market_period')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);

            // Attribute fleksibel untuk kebutuhan inspeksi
            $table->json('specifications')
                ->nullable();

            $table->timestamps();

            // 👉 UNIQUE gabungan brand_id + model_id + type_id + year
            $table->unique([
                'brand_id',
                'model_id',
                'type_id',
                'year',
                'cc',
                'fuel_type',
                'transmission_id',
                // 'engine_type',
                'origin_id',
                // 'generation',
                // 'market_period',
                // 'is_active'
            ], 'vehicle_details_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_details');
    }
};
