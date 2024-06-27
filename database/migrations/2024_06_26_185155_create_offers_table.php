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
            $table->enum('provider', ['satstralia', 'temple', 'lake', 'veneto', 'exp']);
            $table->boolean('accepted')->default(false);
            $table->timestamp('expires_at');
            $table->enum('side', ['buy', 'sell']);
            $table->integer('currency');
            $table->decimal('amount', 20, 8)->nullable();
            $table->boolean('has_range');
            $table->decimal('min_amount', 20, 8);
            $table->decimal('max_amount', 20, 8);
            $table->string('payment_method');
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
