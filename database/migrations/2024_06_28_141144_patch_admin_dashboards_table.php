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
       // add min satoshi profit
       Schema::table('admin_dashboards', function (Blueprint $table) {
          $table->integer('min_satoshi_profit')->default(5000);
       });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_dashboards', function (Blueprint $table) {
            $table->dropColumn('min_satoshi_profit');
        });
    }
};
