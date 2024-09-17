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
        Schema::create('posted_offer_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->enum('type', ['buy', 'sell'])->default('sell');
            $table->json('provider');
            $table->string('currency');
            $table->decimal('premium', 10, 2);
            $table->decimal('min_amount', 10, 2);
            $table->decimal('max_amount', 10, 2)->nullable();
            // latitude and longitude
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('payment_methods');
            $table->integer('bond_size');
            $table->boolean('auto_create');
            $table->integer('quantity')->default(1);
            $table->integer('cooldown')->default(0);
            $table->integer('ttl')->default(86400);
            $table->integer('escrow_time')->default(28800);
            $table->timestamp('last_created')->nullable();
            $table->timestamp('last_accepted')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posted_offer_templates');
    }
};
