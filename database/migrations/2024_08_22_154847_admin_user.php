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
        // make a new item in user table for the admin
        \App\Models\User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => \Illuminate\Support\Facades\Hash::make('MLZ6+IOM5+dDMD12nU9uqk'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // remove the admin user
        \App\Models\User::where('email', 'tmwclaxton@gmail.com')->delete();
    }
};
