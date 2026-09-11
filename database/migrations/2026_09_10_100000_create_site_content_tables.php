<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 200)->unique();
            $table->string('title', 255);
            $table->string('excerpt', 500)->nullable();
            $table->longText('content');
            $table->string('cover_image_path', 500)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('author_name', 120)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
        });

        Schema::create('site_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 200)->unique();
            $table->string('title', 255);
            $table->longText('content');
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_pages');
        Schema::dropIfExists('site_posts');
    }
};
