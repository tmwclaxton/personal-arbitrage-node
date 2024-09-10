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
            $table->boolean('panicButton')->default(false);
            $table->boolean('autoReward')->default(false);
            $table->boolean('autoAccept')->default(false);
            $table->boolean('autoBond')->default(true);
            $table->boolean('autoEscrow')->default(true);
            $table->boolean('autoMessage')->default(true);
            $table->boolean('autoConfirm')->default(false);
            $table->boolean('autoCreate')->default(false);
            $table->time('auto_accept_start_time')->default('09:00:00');
            $table->time('auto_accept_end_time')->default('22:00:00');
            $table->boolean('scheduler')->default(false);

            $table->integer('localBalance')->default(0);
            $table->integer('remoteBalance')->default(0);
            $table->json('channelBalances')->nullable();

            $table->decimal('sell_premium', 5, 2)->default(2);
            $table->decimal('buy_premium', 5, 2)->default(-1);
            $table->integer('min_satoshi_profit')->default(5000);
            $table->integer('min_bond')->default(3);
            $table->integer('max_satoshi_amount')->default(400000);
            $table->integer('max_concurrent_transactions')->default(1);
            $table->json('payment_currencies')->nullable();
            $table->json('payment_methods')->nullable();
            $table->integer('kraken_btc_balance')->default(0);
            $table->integer('ideal_lightning_node_balance')->default(6000000);
            $table->json('provider_statuses')->nullable();

            $table->string('primary_currency')->default('USD');

            $table->string('slack_app_id')->nullable();
            $table->string('slack_client_id')->nullable();
            $table->string('slack_client_secret')->nullable();
            $table->string('slack_signing_secret')->nullable();
            $table->string('slack_bot_token')->nullable();

            $table->boolean('autoTopup')->default(false);
            $table->string('kraken_api_key')->nullable();
            $table->string('kraken_private_key')->nullable();
            $table->boolean('kraken_action')->default(true); // buy btc is false, sell btc is true

            $table->string('umbrel_ip')->nullable();
            $table->string('umbrel_port')->nullable();
            $table->string('umbrel_password')->nullable();
            $table->string('umbrel_topt_key')->nullable();
            // the umbrel token is set automatically
            $table->string('umbrel_token')->nullable();

            $table->string('client_private_key')->nullable();
            $table->string('server_public_key')->nullable();

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
