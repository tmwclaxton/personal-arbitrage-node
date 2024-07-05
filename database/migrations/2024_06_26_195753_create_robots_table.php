<?php

use App\Models\Offer;
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
        Schema::create('robots', function (Blueprint $table) {
            $table->id();
            // this can be the same for many robots so it is not unique
            $table->foreignIdFor(Offer::class);
            $table->unsignedBigInteger('offerIdStorage')->nullable();
            $table->string('provider');
            $table->string('nickname');
            $table->string('token');
            $table->string('sha256');
            $table->string('hash_id');
            $table->text('public_key');
            $table->text('private_key');
            $table->text('public_key_latter')->nullable();
            $table->text('private_key_latter')->nullable();
            $table->bigInteger('earned_rewards')->default(0);
            $table->boolean('wants_stealth')->default(false);
            $table->timestamp('last_login');
            $table->boolean('tg_enabled')->default(false);
            $table->string('tg_token')->nullable();
            $table->string('tg_bot_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('robots');
    }
};
