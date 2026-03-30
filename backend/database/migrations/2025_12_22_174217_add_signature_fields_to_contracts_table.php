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
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'signature_token')) {
                $table->string('signature_token')->nullable()->after('status');
            }
            if (!Schema::hasColumn('contracts', 'signature_token_expires_at')) {
                $table->timestamp('signature_token_expires_at')->nullable()->after('signature_token');
            }
            if (!Schema::hasColumn('contracts', 'signed_by_name')) {
                $table->string('signed_by_name')->nullable()->after('signed_at');
            }
            if (!Schema::hasColumn('contracts', 'signed_by_email')) {
                $table->string('signed_by_email')->nullable()->after('signed_by_name');
            }
            if (!Schema::hasColumn('contracts', 'signature_ip')) {
                $table->string('signature_ip')->nullable()->after('signed_by_email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'signature_token',
                'signature_token_expires_at',
                'signed_at',
                'signed_by_name',
                'signed_by_email',
                'signature_ip'
            ]);
        });
    }
};
