<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('hero_tagline', 200)->nullable();
            $table->string('hero_description', 500)->nullable();
            $table->string('hero_image_path', 500)->nullable();
            $table->string('about_short', 500)->nullable();
            $table->string('facebook_url', 255)->nullable();
            $table->string('instagram_url', 255)->nullable();
            $table->string('youtube_url', 255)->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('contact_address', 500)->nullable();
            $table->string('footer_note', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('site_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 255)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('subject', 200)->nullable();
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_messages');
        Schema::dropIfExists('site_settings');
    }
};
