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
        // add a bunch of items to the payment_methods table i.e Revolut, CashApp, etc
        $models = collect([
            [
                'name' => 'Revolut',
                'logo_url' => 'https://www.fintechfutures.com/files/2021/07/revolut.png',
            ],
            [
                'name' => 'Wise',
                'logo_url' => 'https://figma-alpha-api.s3.us-west-2.amazonaws.com/images/d7c0bb67-155c-4767-9bff-0848c5392cac',
            ],
            [
                'name' => 'Strike',
                'logo_url' => 'https://downloadr2.apkmirror.com/wp-content/uploads/2023/05/79/646793eb39dc3.png',
            ],
            [
                'name' => 'Instant SEPA',
                'logo_url' => 'https://getlogo.net/wp-content/uploads/2020/03/single-euro-payments-area-sepa-logo-vector.png',
            ],
            [
                'name' => 'Faster Payments',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0a/Faster_Payments_logo.svg/202px-Faster_Payments_logo.svg.png?20150403100936',
            ],
            [
                'name' => 'PayPal',
                'logo_url' => 'https://w7.pngwing.com/pngs/632/1015/png-transparent-paypal-logo-computer-icons-payment-paypal-blue-angle-service-thumbnail.png',
            ],
            [
                'name' => 'CashApp',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c5/Square_Cash_app_logo.svg/240px-Square_Cash_app_logo.svg.png',
            ],
            [
                'name' => 'Zelle',
                'logo_url' => 'https://logodownload.org/wp-content/uploads/2022/03/zelle-logo-1.png',
            ],
            [
                'name' => 'Venmo',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/84/Venmo_logo.png/1200px-Venmo_logo.png?20220919164503',
            ],
        ]);

        $models->each(function ($model) {
            \App\Models\PaymentMethod::create($model);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // remove all items from the payment_methods table
        \App\Models\PaymentMethod::query()->delete();
    }
};
