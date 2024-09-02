<script setup>
import {Head, Link, router} from '@inertiajs/vue3';
import Offer from "@/Components/Offer.vue";
import ToggleButton from "@/Components/ToggleButton.vue";
import {onMounted, ref, watch} from "vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import PaymentsInput from "@/Components/PaymentsInput.vue";
import CurrenciesInput from "@/Components/CurrenciesInput.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";

// const props = defineProps({
//     offers: Array,
//     btcPrices: Object,
//     adminDashboard: Object,
// });

const offerType = ref('Buy');
const offerAmount = ref(null);
const speed = ref('Normal');

</script>

<template>
    <Head title="Simple View" />
    <div class="min-h-screen flex flex-col">
        <div class="flex flex-col ">
            <p class="font-bold text-2xl mx-auto text-center py-5">Lightning Arbitrage Solutions</p>
            <p class="text-center ">Automated P2P trading for Bisq and Robosats</p>
        </div>
        <div class="flex flex-row gap-x-2 absolute top-0 w-full">
            <div class="flex flex-col flex-grow opacity-0 h-20">
            </div>

            <div class="flex-shrink-0 my-auto mx-10">
                <Link :href="route('welcome')">
                    <secondary-button class="h-10 my-auto">
                        Advanced View
                    </secondary-button>
                </Link>
            </div>
        </div>
        <div class="border-b border-gray-200 mt-4 dark:border-gray-700"></div>

        <div class="my-auto">
            <!--input for type of offer (buy/sell)-->
            <div class="flex justify-center  flex flex-row gap-x-2">
                <p>{{ offerType ? 'Buy' : 'Sell' }}</p>
                <ToggleButton v-model="offerType" :options="['Buy', 'Sell']"/>
            </div>

            <!--input for amount of offer-->
            <div class="flex justify-center mt-4">
                <TextInput v-model="offerAmount" type="number" placeholder="No. of Satoshis"/>
            </div>

            <!--input for speed of offer-->
            <div class="flex justify-center mt-4 mx-auto w-3620">
                <!--['Slow', 'Normal', 'Fast', 'Extreme']" buttons?-->
                <select v-model="speed"
                        class="w-36 block mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm py-2 px-3 text-base focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="Slow">Slow</option>
                    <option value="Normal">Normal</option>
                    <option value="Fast">Fast</option>
                    <option value="Extreme">Extreme</option>
                </select>
            </div>

            <!--start-->
            <div class="flex justify-center mt-4">
                <PrimaryButton @click="submit">Start</PrimaryButton>
            </div>
        </div>


    </div>
</template>
