<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weddings', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('couples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained('weddings')->onDelete('cascade');
            // Groom
            $table->string('groom_full_name')->nullable();
            $table->string('groom_nickname')->nullable();
            $table->string('groom_photo')->nullable();
            $table->string('groom_father_name')->nullable();
            $table->string('groom_mother_name')->nullable();
            $table->string('groom_instagram_url')->nullable();
            $table->string('groom_child_order')->nullable();
            // Bride
            $table->string('bride_full_name')->nullable();
            $table->string('bride_nickname')->nullable();
            $table->string('bride_photo')->nullable();
            $table->string('bride_father_name')->nullable();
            $table->string('bride_mother_name')->nullable();
            $table->string('bride_instagram_url')->nullable();
            $table->string('bride_child_order')->nullable();
            
            $table->string('music_url')->nullable();
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('wedding_id')->constrained('weddings')->onDelete('cascade');
            $table->string('name');
            $table->string('date')->nullable();
            $table->string('time_start')->nullable();
            $table->string('time_end')->nullable();
            $table->string('timezone')->nullable();
            $table->string('location_name')->nullable();
            $table->text('location_address')->nullable();
            $table->text('location_map_url')->nullable();
            $table->timestamps();
        });

        Schema::create('guests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('wedding_id')->constrained('weddings')->onDelete('cascade');
            $table->string('name');
            $table->string('group')->nullable();
            $table->string('code')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_vip')->default(false);
            $table->integer('max_pax')->default(1);
            $table->string('rsvp_status')->default('pending');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();
        });

        Schema::create('media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('wedding_id')->constrained('weddings')->onDelete('cascade');
            $table->string('url');
            $table->string('type')->default('image');
            $table->integer('size')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained('weddings')->onDelete('cascade');
            $table->json('bank_accounts')->nullable();
            $table->json('gifts')->nullable();
            $table->timestamps();
        });

        Schema::create('rsvps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('wedding_id')->constrained('weddings')->onDelete('cascade');
            $table->uuid('guest_id')->nullable();
            $table->string('name');
            $table->string('status');
            $table->integer('guests_count')->default(1);
            $table->text('wishes')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained('weddings')->onDelete('cascade');
            $table->string('template_id')->default('batik-elegance');
            $table->json('theme_colors')->nullable();
            $table->string('gallery_layout')->default('masonry');
            $table->boolean('moderation_enabled')->default(true);
            $table->boolean('password_protected')->default(false);
            $table->string('password')->nullable();
            $table->boolean('show_watermark')->default(true);
            $table->string('custom_domain')->nullable();
            $table->text('health_protocol')->nullable();
            $table->string('live_stream_url')->nullable();
            $table->text('shipping_address')->nullable();
            $table->json('seo_meta')->nullable();
            $table->string('qris_image_url')->nullable();
            $table->timestamps();
        });

        Schema::create('wishes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('wedding_id')->constrained('weddings')->onDelete('cascade');
            $table->string('name');
            $table->text('message');
            $table->boolean('is_approved')->default(true);
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishes');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('rsvps');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('media');
        Schema::dropIfExists('guests');
        Schema::dropIfExists('events');
        Schema::dropIfExists('couples');
        Schema::dropIfExists('weddings');
    }
};
