<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('property_type'); // 'local' or 'treb'
            $table->string('property_id'); // Can be either local ID or TREB ListingKey
            $table->json('property_data')->nullable(); // Store TREB property data snapshot
            $table->timestamps();
            $table->softDeletes();

            // Ensure a user can't favorite the same property twice
            $table->unique(['user_id', 'property_type', 'property_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
