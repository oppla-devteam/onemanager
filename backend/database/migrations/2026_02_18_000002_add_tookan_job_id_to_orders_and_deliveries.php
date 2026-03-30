<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('tookan_job_id')->nullable()->index()->after('oppla_data');
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->string('tookan_job_id')->nullable()->index()->after('oppla_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('tookan_job_id');
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn('tookan_job_id');
        });
    }
};
