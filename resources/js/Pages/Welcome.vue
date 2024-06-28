<script setup>
import {Head, Link, router} from '@inertiajs/vue3';
import Offer from "@/Components/Offer.vue";
import ToggleButton from "@/Components/ToggleButton.vue";
import {ref} from "vue";
import TextInput from "@/Components/TextInput.vue";

defineProps({
    offers: Array,
    btcPrices: Object,
    adminDashboard: Object,
});

const autoTopup = ref(false);

//auto refresh page every 10 seconds soft
setInterval(() => {
    router.reload();
}, 10000);

</script>

<template>
    <Head title="Welcome" />
    <div class="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">

        <div class="w-full flex flex-row gap-x-8 mx-auto pt-10 justify-center ">
            <div v-if="btcPrices.length > 0" v-for="btcPrice in btcPrices" :key="btcPrice.id">
                <div class="text-center">
                    <span class="text-2xl font-bold">{{ btcPrice.currency }}</span>
                </div>
                <div class="text-center">
                    <span class="text-2xl font-bold">{{ btcPrice.price }}</span>
                </div>
            </div>
            <div v-else>
                <p>Loading BTC prices...</p>
            </div>
        </div>

        <div v-if="adminDashboard" class="flex flex-row mx-5 gap-x-5 my-5">
            <div class="text-left border-r border-black dark:border-white/70 pr-5">
                <p class=""><span class="font-bold text-xl mb-2">Wallet:</span></p>

                <p class=""><span class="font-bold">Lighting Wallet Balance:</span> {{ adminDashboard.localBalance }} </p>
                <p class=""><span class="font-bold">Remote Balance:</span> {{ adminDashboard.remoteBalance }} </p>
                <p class=""><span class="font-bold">Auto Topup:</span>     <ToggleButton v-model="autoTopup" size="sm" activeColor="bg-green-500" inactiveColor="bg-red-500" /></p>
            </div>
            <div class="text-left border-r border-black dark:border-white/70 pr-5">
                <p class=""><span class="font-bold text-xl mb-2">Automation:</span></p>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Auto Accept</span><ToggleButton v-model="autoTopup" size="sm" activeColor="bg-green-500" inactiveColor="bg-red-500" /></div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Auto Bond</span><ToggleButton v-model="autoTopup" size="sm" activeColor="bg-green-500" inactiveColor="bg-red-500" /></div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Auto Escrow</span><ToggleButton v-model="autoTopup" size="sm" activeColor="bg-green-500" inactiveColor="bg-red-500" /></div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Auto Chat</span><ToggleButton v-model="autoTopup" size="sm" activeColor="bg-green-500" inactiveColor="bg-red-500" /></div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Auto Confirm</span><ToggleButton v-model="autoTopup" size="sm" activeColor="bg-green-500" inactiveColor="bg-red-500" /></div>
            </div>
            <div class="text-left pl-5 flex flex-col gap-y-1 border-r border-black dark:border-white/70 pr-5">
                <div class="flex flex-row justify-between items-center"><span class="font-bold text-xl mb-2">Offer Selection:</span></div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Sell Premium: </span><TextInput :model-value="adminDashboard.sell_premium" /></div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Buy Premium: </span><TextInput :model-value="adminDashboard.buy_premium" /></div>
            </div>
            <div class="text-left pl-5 flex flex-col gap-y-1 border-r border-black dark:border-white/70 pr-5">
                <div class="flex flex-row justify-between items-center"><span class="font-bold text-xl mb-2">More Config:</span></div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Revolut Tag: </span><TextInput model-value="@tobyclaxton" /></div>

            </div>
            <div class="text-left pl-5 flex flex-col gap-y-1 border-r border-black dark:border-white/70 pr-5">
                <div class="flex flex-row justify-between items-center"><span class="font-bold text-xl mb-2">Statistics:</span></div>
            </div>

        </div>

        <div
            class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white"
        >
            <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">

                <main class=" ">
                    <div class="grid grid-cols-3 gap-4 mx-auto" v-if="offers.length > 0">
                        <Offer v-for="offer in offers" :key="offer.robosatsId" :offer="offer" />
                    </div>
                    <div class="text-center" v-else>
                        <p class="text-lg">No offers available</p>
                    </div>
                </main>

                <footer class="py-16 text-center text-sm text-black dark:text-white/70">
                </footer>
            </div>
        </div>
    </div>
</template>
