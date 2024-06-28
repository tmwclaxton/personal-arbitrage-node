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
        Schema::create('admin_dashboards', function (Blueprint $table) {
            $table->id();
            $table->integer('localBalance')->default(0);
            $table->integer('remoteBalance')->default(0);
            $table->boolean('autoAccept')->default(false);
            $table->boolean('autoBond')->default(false);
            $table->boolean('autoEscrow')->default(false);
            $table->boolean('autoMessage')->default(false);
            $table->boolean('autoConfirm')->default(false);
            $table->decimal('sell_premium', 5, 2)->default(2);
            $table->decimal('buy_premium', 5, 2)->default(-1);
            $table->integer('trade_volume_satoshis')->default(0);
            $table->integer('satoshi_profit')->default(0);
            $table->integer('satoshi_fees')->default(0);
            $table->json('allowed_payment_methods')->nullable();
            $table->json('allowed_providers')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_dashboards');
    }
};
