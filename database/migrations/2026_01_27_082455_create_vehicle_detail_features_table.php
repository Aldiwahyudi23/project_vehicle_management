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
        Schema::create('vehicle_detail_features', function (Blueprint $table) {
            $table->id();
             $table->foreignId('vehicle_detail_id')->constrained('vehicle_details')->onDelete('cascade');
            $table->foreignId('feature_id')->constrained('vehicle_features')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_detail_features');
    }
};
