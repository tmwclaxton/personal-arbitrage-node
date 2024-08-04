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
        // add the new column
        Schema::table('admin_dashboards', function (Blueprint $table) {
            $table->float('revolut_balance')->nullable();
            $table->float('wise_balance')->nullable();
            $table->float('kraken_balance')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // drop the column
        Schema::table('admin_dashboards', function (Blueprint $table) {
            $table->dropColumn('revolut_balance');
            $table->dropColumn('wise_balance');
            $table->dropColumn('kraken_balance');
        });
    }
};
