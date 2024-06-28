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
        Schema::create('robots', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('nickname');
            $table->string('hash_id')->unique();
            $table->text('public_key');
            $table->text('encrypted_private_key');
            $table->bigInteger('earned_rewards')->default(0);
            $table->boolean('wants_stealth')->default(false);
            $table->timestamp('last_login');
            $table->boolean('tg_enabled')->default(false);
            $table->string('tg_token')->nullable();
            $table->string('tg_bot_name')->nullable();
            $table->boolean('found')->default(false);
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
