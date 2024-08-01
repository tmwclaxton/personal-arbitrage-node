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
        Schema::create('btc_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Payment::class)->nullable();
            $table->string('tx_id')->unique();
            $table->text('primaryDescription')->nullable();
            $table->string('ref_id')->nullable();
            $table->string('user_ref')->nullable();
            $table->string('status')->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('open_timestamp')->nullable();
            $table->timestamp('start_timestamp')->nullable();
            $table->timestamp('expire_timestamp')->nullable();
            $table->timestamp('close_timestamp')->nullable();
            $table->string('description_pair')->nullable();
            $table->string('description_type')->nullable();
            $table->string('description_order_type')->nullable();
            $table->decimal('description_price', 16, 8)->nullable();
            $table->decimal('description_secondary_price', 16, 8)->nullable();
            $table->string('description_leverage')->nullable();
            $table->string('description_order')->nullable();
            $table->string('description_close')->nullable();
            $table->decimal('volume', 16, 8)->nullable();
            $table->decimal('volume_executed', 16, 8)->nullable();
            $table->decimal('cost', 16, 8)->nullable();
            $table->decimal('fee', 16, 8)->nullable();
            $table->decimal('price', 16, 8)->nullable();
            $table->decimal('stop_price', 16, 8)->nullable();
            $table->decimal('limit_price', 16, 8)->nullable();
            $table->json('miscellaneous')->nullable();
            $table->json('flags')->nullable();
            $table->json('trades')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('btc_purchases');
    }
};
