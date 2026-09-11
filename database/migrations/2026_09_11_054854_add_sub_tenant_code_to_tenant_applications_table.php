<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_applications', function (Blueprint $table): void {
            $table->string('sub_tenant_code')->nullable()->after('instance_url');
            $table->dropUnique(['tenant_id', 'application_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tenant_applications', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'application_id']);
            $table->dropColumn('sub_tenant_code');
        });
    }
};
