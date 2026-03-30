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
        Schema::table('contract_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('contract_templates', 'html_template')) {
                $table->longText('html_template')->nullable()->after('required_fields');
            }
            if (!Schema::hasColumn('contract_templates', 'placeholder_mappings')) {
                $table->json('placeholder_mappings')->nullable()->after('html_template');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->dropColumn(['html_template', 'placeholder_mappings']);
        });
    }
};
