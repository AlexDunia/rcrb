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
        Schema::create('land_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->enum('land_type', [
                'residential_lot',
                'commercial_lot',
                'agricultural',
                'recreational',
                'industrial_lot'
            ]);
            $table->string('topography');
            $table->string('soil_type');
            $table->boolean('utilities_available')->default(false);
            $table->decimal('road_frontage', 10, 2)->comment('in feet');
            $table->string('zoning');
            $table->string('current_use');
            $table->string('potential_use');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('land_properties');
    }
};
