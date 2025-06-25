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
        Schema::create('commercial_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->enum('commercial_type', [
                'office',
                'retail',
                'industrial',
                'warehouse',
                'restaurant',
                'mixed_use'
            ]);
            $table->decimal('total_area', 10, 2)->comment('in square feet');
            $table->integer('floors');
            $table->integer('units');
            $table->integer('loading_docks')->default(0);
            $table->decimal('ceiling_height', 5, 2)->comment('in feet');
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
        Schema::dropIfExists('commercial_properties');
    }
};
