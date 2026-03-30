<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('file_path');
            }
            if (!Schema::hasColumn('contracts', 'signed_pdf_path')) {
                $table->string('signed_pdf_path')->nullable()->after('pdf_path');
            }
            if (!Schema::hasColumn('contracts', 'partner_ragione_sociale')) {
                $table->string('partner_ragione_sociale')->nullable()->after('terms');
            }
            if (!Schema::hasColumn('contracts', 'partner_piva')) {
                $table->string('partner_piva')->nullable()->after('partner_ragione_sociale');
            }
            if (!Schema::hasColumn('contracts', 'partner_sede_legale')) {
                $table->string('partner_sede_legale')->nullable()->after('partner_piva');
            }
            if (!Schema::hasColumn('contracts', 'partner_email')) {
                $table->string('partner_email')->nullable()->after('partner_sede_legale');
            }
            if (!Schema::hasColumn('contracts', 'partner_legale_rappresentante')) {
                $table->string('partner_legale_rappresentante')->nullable()->after('partner_email');
            }
            if (!Schema::hasColumn('contracts', 'partner_iban')) {
                $table->string('partner_iban')->nullable()->after('partner_legale_rappresentante');
            }
            if (!Schema::hasColumn('contracts', 'costo_attivazione')) {
                $table->decimal('costo_attivazione', 10, 2)->nullable()->after('partner_iban');
            }
            if (!Schema::hasColumn('contracts', 'periodo_mesi')) {
                $table->integer('periodo_mesi')->nullable()->after('costo_attivazione');
            }
            if (!Schema::hasColumn('contracts', 'territorio')) {
                $table->string('territorio')->nullable()->after('periodo_mesi');
            }
            if (!Schema::hasColumn('contracts', 'miglior_prezzo_garantito')) {
                $table->boolean('miglior_prezzo_garantito')->default(false)->after('territorio');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'pdf_path',
                'signed_pdf_path',
                'partner_ragione_sociale',
                'partner_piva',
                'partner_sede_legale',
                'partner_email',
                'partner_legale_rappresentante',
                'partner_iban',
                'costo_attivazione',
                'periodo_mesi',
                'territorio',
                'miglior_prezzo_garantito',
            ]);
        });
    }
};
