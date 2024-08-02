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
        Schema::create('robosats_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Offer::class)->constrained()->cascadeOnDelete();
            $table->integer('index');
            $table->string('message');
            $table->string('user_nick');
            $table->timestamp('sent_at');
            $table->boolean('sent_to_discord')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('robosats_chat_messages');
    }
};
