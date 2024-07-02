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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('robosatsId')->unique();
            $table->string('provider');
            $table->boolean('accepted')->default(false);
            $table->timestamp('expires_at');
            $table->string('type');
            $table->string('currency');
            $table->decimal('amount', 20, 8)->nullable();
            $table->bigInteger('satoshi_amount_profit')->nullable();
            $table->boolean('has_range');
            $table->decimal('min_amount', 20, 8)->nullable();
            $table->unsignedBigInteger('min_satoshi_amount')->nullable();
            $table->bigInteger('min_satoshi_amount_profit')->nullable();
            $table->decimal('max_amount', 20, 8)->nullable();
            $table->unsignedBigInteger('max_satoshi_amount')->nullable();
            $table->bigInteger('max_satoshi_amount_profit')->nullable();
            $table->json('payment_methods');
            $table->boolean('is_explicit');
            $table->decimal('premium', 5, 2);
            $table->unsignedBigInteger('satoshis')->nullable();
            $table->unsignedBigInteger('maker');
            $table->integer('escrow_duration');
            $table->decimal('bond_size', 10, 2);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('maker_nick');
            $table->string('maker_hash_id');
            $table->unsignedBigInteger('satoshis_now');
            $table->unsignedBigInteger('price');
            $table->string('maker_status');

            $table->integer('status')->default(0);
            $table->text('status_message')->nullable();
            $table->unsignedBigInteger('taker')->nullable();
            $table->integer('total_secs_exp')->default(0);
            $table->boolean('is_maker')->default(false);
            $table->boolean('is_taker')->default(false);
            $table->boolean('is_participant')->default(false);
            $table->string('taker_nick')->nullable();
            $table->string('taker_hash_id')->nullable();
            $table->string('taker_status')->nullable();
            $table->boolean('is_buyer')->default(false);
            $table->boolean('is_seller')->default(false);
            $table->boolean('is_fiat_sent')->default(false);
            $table->boolean('is_disputed')->default(false);
            $table->string('ur_nick')->nullable();
            $table->boolean('maker_locked')->default(false);
            $table->boolean('taker_locked')->default(false);
            $table->boolean('escrow_locked')->default(false);
            $table->unsignedBigInteger('trade_satoshis')->nullable();
            $table->boolean('asked_for_cancel')->default(false);
            $table->integer('chat_last_index')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
