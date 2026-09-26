<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // One-time token minted when the "New Purchase Request" form is rendered.
    // The unique index makes a double-click / re-submitted form idempotent at
    // the database level: the second insert collides and the original request
    // is returned instead of creating a duplicate. Nullable so existing rows
    // (and non-form callers) are unaffected — NULLs never collide in a unique index.
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->string('submission_token', 64)->nullable()->after('number');
            $table->unique(['tenant_id', 'submission_token'], 'purchase_requests_tenant_token_unique');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropUnique('purchase_requests_tenant_token_unique');
            $table->dropColumn('submission_token');
        });
    }
};
