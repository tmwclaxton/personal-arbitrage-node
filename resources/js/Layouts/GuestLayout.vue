<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
</script>

<template>
    <div class="min-h-screen flex flex-col   pt-6 sm:pt-0 ">
        <div>
            <p class="font-bold text-2xl mx-auto text-center py-5">Lightning Arbitrage Solutions</p>

            <div class="w-full flex flex-row flex-wrap gap-3 mt-2 mx-auto justify-center">
                <Link :href="route('welcome')" >
                    <secondary-button class="h-12">
                        Live Offers
                    </secondary-button>
                </Link>

                <Link :href="route('offers.completed')" >
                    <secondary-button class="h-12">
                        Completed Offers
                    </secondary-button>
                </Link>

                <Link :href="route('offers.posting.index')" >
                    <secondary-button class="h-12">
                        Offer Templates
                    </secondary-button>
                </Link>

                <Link :href="route('dashboard.index')">
                    <secondary-button  class="h-12">
                        Config
                    </secondary-button>
                </Link>
                <Link :href="route('transactions.index')" >
                    <secondary-button class="h-12">
                        Transactions
                    </secondary-button>
                </Link>
                <Link :href="route('payments.index')" class="opacity-50 pointer-events-none">
                    <secondary-button class="h-12">
                        Payments
                    </secondary-button>
                </Link>
                <Link :href="route('purchases.index')" >
                    <secondary-button class="h-12">
                        Bitcoin Purchases
                    </secondary-button>
                </Link>
                <Link :href="route('graphs.index')" >
                    <secondary-button class="h-12">
                        Graphs
                    </secondary-button>
                </Link>
                <Link :href="route('graphs.index')" >
                    <secondary-button class="h-12">
                        Tabulated Data
                    </secondary-button>
                </Link>
            </div>
            <div class="my-5 border-b-2 border-gray-300 dark:border-zinc-700"></div>
        </div>

        <div
            class=""
        >
            <slot />
        </div>
    </div>
</template>
