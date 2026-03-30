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
        Schema::table('clients', function (Blueprint $table) {
            // Aggiungi colonna telefono se non esiste
            if (!Schema::hasColumn('clients', 'telefono')) {
                $table->string('telefono')->nullable()->after('phone');
            }
            
            // Aggiungi colonna codice_destinatario se non esiste
            if (!Schema::hasColumn('clients', 'codice_destinatario')) {
                $table->string('codice_destinatario')->nullable()->after('codice_fiscale');
            }
            
            // Aggiungi colonna iban se non esiste
            if (!Schema::hasColumn('clients', 'iban')) {
                $table->string('iban')->nullable()->after('pec');
            }
            
            // Aggiungi colonna is_active se non esiste
            if (!Schema::hasColumn('clients', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
            
            // Aggiungi colonna fatture_in_cloud_id se non esiste
            if (!Schema::hasColumn('clients', 'fatture_in_cloud_id')) {
                $table->unsignedBigInteger('fatture_in_cloud_id')->nullable()->after('stripe_customer_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'telefono')) {
                $table->dropColumn('telefono');
            }
            if (Schema::hasColumn('clients', 'codice_destinatario')) {
                $table->dropColumn('codice_destinatario');
            }
            if (Schema::hasColumn('clients', 'iban')) {
                $table->dropColumn('iban');
            }
            if (Schema::hasColumn('clients', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('clients', 'fatture_in_cloud_id')) {
                $table->dropColumn('fatture_in_cloud_id');
            }
        });
    }
};
