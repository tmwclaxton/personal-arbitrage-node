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
            $table->string('provider');
            $table->string('currency');
            $table->decimal('premium', 10, 2);
            $table->decimal('min_amount', 10, 2);
            $table->decimal('max_amount', 10, 2)->nullable();
            $table->json('payment_methods');
            $table->integer('bond_size');
            $table->boolean('auto_create');
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
