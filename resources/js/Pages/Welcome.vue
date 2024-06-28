<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Offer from "@/Components/Offer.vue";

defineProps({
    offers: Array,
    btcPrices: Object,
});

</script>

<template>
    <Head title="Welcome" />
    <div class="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">

        <div class="w-full flex flex-row gap-x-8 mx-auto mt-10 justify-center ">
            <div class="flex flex-col" v-for="btcPrice in btcPrices" :key="btcPrice.id">
                <div class="text-center">
                    <span class="text-2xl font-bold">{{ btcPrice.currency }}</span>
                </div>
                <div class="text-center">
                    <span class="text-2xl font-bold">{{ btcPrice.price }}</span>
                </div>
            </div>
        </div>

        <div
            class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white"
        >
            <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
                <header class="grid grid-cols-2 items-center gap-2 py-10 lg:grid-cols-3">
                </header>

                <main class="mt-6">
                    <div class="grid grid-cols-2 gap-4 mx-auto">
                        <Offer v-for="offer in offers" :key="offer.robosatsId" :offer="offer" />
                    </div>
                </main>

                <footer class="py-16 text-center text-sm text-black dark:text-white/70">
                </footer>
            </div>
        </div>
    </div>
</template>
