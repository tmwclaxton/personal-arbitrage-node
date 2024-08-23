<?php

use App\Models\Transaction;
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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Transaction::class)->nullable();
            $table->string('payment_method');
            $table->string('payment_currency');
            $table->string('payment_reference')->nullable();
            $table->decimal('payment_amount', 16, 2);
            $table->string('platform_transaction_id')->unique();
            $table->string('platform_account_id');
            $table->string('platform_description');
            $table->json('platform_entity');
            $table->timestamps();
            $table->timestamp('payment_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
