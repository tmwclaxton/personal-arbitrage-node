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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained();
            $table->text('bond_invoice')->nullable();
            $table->text('escrow_invoice')->nullable();
            $table->text('lightning_payout_invoice')->nullable();
            $table->integer('bond_attempts')->default(0);
            $table->integer('escrow_attempts')->default(0);
            $table->integer('payment_attempts')->default(0);
            $table->integer('status')->default(0);
            $table->string('status_message')->default('');
            $table->integer('fees')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
