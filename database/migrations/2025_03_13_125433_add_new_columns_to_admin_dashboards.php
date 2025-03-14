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
        Schema::table('admin_dashboards', function (Blueprint $table) {
            $table->boolean('email_reporting_enabled')->default(false);
            $table->string('email_reporting_recipient')->nullable();
            $table->text('reporting_message')->nullable();
            $table->integer('email_reporting_auto_delay')->default(60); // seconds
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_dashboards', function (Blueprint $table) {
            $table->dropColumn([
                'email_reporting_enabled',
                'email_reporting_recipient',
                'reporting_message',
                'email_reporting_auto_delay'
            ]);
        });
    }
};
