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
            $table->integer('sitting_sell_offers_count')->default(0);
            $table->float('sitting_sell_offers_min_premium')->nullable(0);
            $table->float('sitting_sell_offers_max_premium')->nullable(0);
            $table->float('sitting_sell_offers_set_aside')->nullable(0);
            $table->boolean('autoCreate')->default(false);
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
            $table->dropColumn('sitting_sell_offers_count');
            $table->dropColumn('sitting_sell_offers_premium');
        });
    }
};
