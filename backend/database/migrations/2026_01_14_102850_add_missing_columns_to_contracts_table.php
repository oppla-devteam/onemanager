<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Aggiungi 'contract_type' come nuova colonna STRING (non ENUM)
            if (!Schema::hasColumn('contracts', 'contract_type')) {
                $table->string('contract_type')->default('servizio')->after('description');
            }
        });
        
        // Se esiste colonna 'type', copia i dati e poi elimina
        if (Schema::hasColumn('contracts', 'type')) {
            DB::statement("UPDATE contracts SET contract_type = `type` WHERE contract_type IS NULL OR contract_type = ''");
            
            Schema::table('contracts', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
        
        Schema::table('contracts', function (Blueprint $table) {
            // Aggiungi 'created_by' se non esiste
            if (!Schema::hasColumn('contracts', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('note')->constrained('users');
            }
            
            // Aggiungi 'assigned_to' se non esiste
            if (!Schema::hasColumn('contracts', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->after('created_by')->constrained('users');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Rimuovi 'created_by'
            if (Schema::hasColumn('contracts', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            
            // Rimuovi 'assigned_to'
            if (Schema::hasColumn('contracts', 'assigned_to')) {
                $table->dropForeign(['assigned_to']);
                $table->dropColumn('assigned_to');
            }
            
            // Ricrea colonna 'type' come ENUM
            if (!Schema::hasColumn('contracts', 'type')) {
                $table->enum('type', ['servizio', 'fornitura', 'partnership', 'altro'])->default('servizio')->after('description');
            }
        });
        
        // Copia dati da contract_type a type
        if (Schema::hasColumn('contracts', 'contract_type')) {
            DB::statement("UPDATE contracts SET `type` = contract_type WHERE `type` IS NULL OR `type` = ''");
            
            Schema::table('contracts', function (Blueprint $table) {
                $table->dropColumn('contract_type');
            });
        }
    }
};
