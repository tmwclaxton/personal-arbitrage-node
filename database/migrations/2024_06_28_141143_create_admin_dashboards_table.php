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

            $table->string('revolut_code')->nullable();
            $table->integer('localBalance')->default(0);
            $table->integer('remoteBalance')->default(0);
            $table->json('channelBalances')->nullable();
            $table->boolean('panicButton')->default(false);
            $table->boolean('autoTopup')->default(false);
            $table->boolean('autoAccept')->default(false);
            $table->boolean('autoBond')->default(true);
            $table->boolean('autoEscrow')->default(true);
            $table->boolean('autoMessage')->default(true);
            $table->boolean('autoConfirm')->default(false);
            $table->decimal('sell_premium', 5, 2)->default(2);
            $table->decimal('buy_premium', 5, 2)->default(-1);
            $table->integer('min_satoshi_profit')->default(5000);
            $table->integer('max_concurrent_transactions')->default(1);
            $table->integer('max_wait_time')->default(10000);

            $table->json('payment_currencies')->nullable();
            $table->json('payment_methods')->nullable();
            $table->integer('trade_volume_satoshis')->default(0);
            $table->integer('satoshi_profit')->default(0);
            $table->integer('satoshi_fees')->default(0);
            $table->json('allowed_payment_methods')->nullable(); //->default(json_encode(['Revolut', 'Paypal Friends & Family', 'Wise']));
            $table->json('allowed_providers')->nullable();
            $table->string('umbrel_token')->nullable()->default('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJwcm94eVRva2VuIjp0cnVlLCJpYXQiOjE3MTk0ODU5MTUsImV4cCI6MTcyMDA5MDcxNX0.u6ZEoMfrRykoE1YOLWL08auNwp_4VRuuxU8qu3CT8OQ');
            $table->string('revolut_handle')->nullable()->default('@tobyclaxton');
            $table->string('paypal_handle')->nullable()->default('@tobyclaxton');
            $table->string('wise_handle')->nullable()->default('@tobymatthewwilliamc');
            $table->string('strike_handle')->nullable();
            $table->string('cashapp_handle')->nullable();
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
