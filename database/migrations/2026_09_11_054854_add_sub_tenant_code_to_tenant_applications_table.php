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
            // MySQL refuses to drop the composite unique index while it is the
            // only index backing the `tenant_id` foreign key, so create the
            // non-unique replacement first: one holding tenant may now link
            // several instances/villages of the same application.
            $table->index(['tenant_id', 'application_id']);
            $table->dropUnique(['tenant_id', 'application_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tenant_applications', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'application_id']);
            $table->dropIndex(['tenant_id', 'application_id']);
            $table->dropColumn('sub_tenant_code');
        });
    }
};
