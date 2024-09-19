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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->integer('preference')->default(0);
            $table->string('name')->unique();
            $table->text('handle')->nullable();
            $table->text('custom_message')->nullable();
            $table->string('logo_url')->nullable();
            $table->json('allowed_currencies')->nullable();
            $table->decimal('specific_buy_premium', 5, 2)->nullable();
            $table->decimal('specific_sell_premium', 5, 2)->nullable();
            $table->boolean('ask_for_reference')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
