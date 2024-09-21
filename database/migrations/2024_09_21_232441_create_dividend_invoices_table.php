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
        Schema::create('dividend_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_id');
            $table->text('lightning_invoice');
            $table->string('status');
            $table->timestamp('creation_date');
            $table->timestamp('due_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dividend_invoices');
    }
};
