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
        Schema::table('tenant_applications', function (Blueprint $table): void {
            $table->string('connection_status', 20)->nullable()->after('notes');
            $table->unsignedInteger('connection_latency_ms')->nullable()->after('connection_status');
            $table->timestamp('connection_checked_at')->nullable()->after('connection_latency_ms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_applications', function (Blueprint $table): void {
            $table->dropColumn(['connection_status', 'connection_latency_ms', 'connection_checked_at']);
        });
    }
};
