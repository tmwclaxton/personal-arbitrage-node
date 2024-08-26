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
        // add a new column for scheduling the auto accept i.e. start and end time
        Schema::table('admin_dashboards', function (Blueprint $table) {
            $table->time('auto_accept_start_time')->default('09:00:00');
            $table->time('auto_accept_end_time')->default('22:00:00');
            $table->boolean('scheduler')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // drop the column
        Schema::table('admin_dashboards', function (Blueprint $table) {
            $table->dropColumn('auto_accept_start_time');
            $table->dropColumn('auto_accept_end_time');
            $table->dropColumn('scheduler');
        });
    }
};
