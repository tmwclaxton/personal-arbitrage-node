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
        // add a nullable foreign key to the offers table to store the template id
        Schema::table('offers', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\PostedOfferTemplate::class)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('posted_offer_template_id');
        });
    }
};
