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
            $table->boolean('panicButton')->default(false);
            $table->boolean('autoTopup')->default(false);
            $table->boolean('autoAccept')->default(false);
            $table->boolean('autoBond')->default(false);
            $table->boolean('autoEscrow')->default(false);
            $table->boolean('autoMessage')->default(false);
            $table->boolean('autoConfirm')->default(false);
            $table->decimal('sell_premium', 5, 2)->default(2);
            $table->decimal('buy_premium', 5, 2)->default(-1);
            $table->json('payment_methods')->nullable();
            $table->integer('trade_volume_satoshis')->default(0);
            $table->integer('satoshi_profit')->default(0);
            $table->integer('satoshi_fees')->default(0);
            $table->json('allowed_payment_methods')->nullable();
            $table->json('allowed_providers')->nullable();
            $table->string('umbrel_token')->nullable()->default('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJwcm94eVRva2VuIjp0cnVlLCJpYXQiOjE3MTk0MzI5MzQsImV4cCI6MTcyMDAzNzczNH0.31qKPyd1zRoySVRPVzisbTxO_FljIisBOHJFyJs6JYc');
            $table->string('revolut_handle')->nullable()->default('@tobyclaxton');
            $table->string('paypal_handle')->nullable();
            $table->string('cashapp_handle')->nullable();
            $table->string('strike_handle')->nullable();
            $table->string('wise_handle')->nullable();
            $table->string('instant_sepa_handle')->nullable();
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
