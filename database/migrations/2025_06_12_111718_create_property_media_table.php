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
        Schema::create('property_media', function (Blueprint $table) {
            $table->id();
            $table->uuid('media_key')->unique();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->string('resource_name')->default('Property'); // Property, Office, Member
            $table->string('resource_record_key'); // Links to the specific resource (property_id, office_id, member_id)
            $table->string('media_type'); // image, video, virtual-tour, document
            $table->string('media_category')->nullable(); // Primary, Secondary, etc.
            $table->string('image_size_description')->nullable(); // Large, Medium, Small, etc.
            $table->string('media_url');
            $table->string('media_caption')->nullable();
            $table->text('media_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order_index')->default(0);
            $table->timestamp('modification_timestamp')->useCurrent()->useCurrentOnUpdate();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('resource_name');
            $table->index('resource_record_key');
            $table->index('media_type');
            $table->index('modification_timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_media');
    }
};
