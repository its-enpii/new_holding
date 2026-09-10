<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_caches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_application_id')->constrained('tenant_applications')->cascadeOnDelete();
            $table->string('report_type');
            $table->string('period', 8);
            $table->json('payload');
            $table->timestamp('fetched_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['tenant_application_id', 'report_type', 'period']);
            $table->index(['expires_at', 'tenant_application_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_caches');
    }
};
