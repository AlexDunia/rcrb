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
        Schema::create('residential_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->enum('residential_type', [
                'single_family',
                'apartment',
                'townhouse',
                'duplex',
                'condo',
                'villa'
            ]);
            $table->integer('bedrooms');
            $table->integer('bathrooms');
            $table->integer('total_rooms');
            $table->decimal('floor_area', 10, 2)->comment('in square feet');
            $table->integer('stories');
            $table->boolean('basement')->default(false);
            $table->boolean('garage')->default(false);
            $table->integer('garage_size')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residential_properties');
    }
};
