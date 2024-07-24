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
        Schema::create('monzo_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('access_token');
            $table->string('refresh_token');
            $table->unsignedInteger('expires');
            $table->string('user_id');
            $table->string('client_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monzo_access_tokens');
    }
};
