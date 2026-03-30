<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aggiungi campi per workflow firma
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'signature_token')) {
                $table->string('signature_token')->nullable()->after('signed_pdf_path');
                $table->timestamp('signature_token_expires_at')->nullable()->after('signature_token');
            }
            
            if (!Schema::hasColumn('contracts', 'html_content')) {
                $table->longText('html_content')->nullable()->after('terms');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['signature_token', 'signature_token_expires_at', 'html_content']);
        });
    }
};
