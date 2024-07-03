<script setup>
import {Head, Link, router} from '@inertiajs/vue3';
import Offer from "@/Components/Offer.vue";
import ToggleButton from "@/Components/ToggleButton.vue";
import {onMounted, ref, watch} from "vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import PaymentsInput from "@/Components/PaymentsInput.vue";
import CurrenciesInput from "@/Components/CurrenciesInput.vue";

const props = defineProps({
    offers: Array,
    btcPrices: Object,
    adminDashboard: Object,
});

const accessOffers = ref(props.offers);
const channelBalances = ref(JSON.parse(props.adminDashboard.channelBalances));


//auto refresh page every 10 seconds soft
setInterval(() => {
    const response = axios.get(route('offers.index')).then(response => {

        // if length of offers has increased, play newOffer.mp3
        if (accessOffers.value !== null &&
            response.data.offers.length > accessOffers.value.length) {
            console.log('new offer');
            // play mp3
            const audio = new Audio('/sounds/newOffer.mp3');
            audio.play();
        }

        // iterate over offers and check if any status has changed
        // if status has changed, play mp3
        for (let i = 0; i < response.data.offers.length; i++) {
            if (accessOffers.value[i] !== undefined && accessOffers.value[i] !== null &&
                response.data.offers[i].transaction !== null && accessOffers.value[i].transaction !== undefined
                && response.data.offers[i].transaction.status !== accessOffers.value[i].transaction.status) {
                console.log('status has changed');
                // play mp3
                const audio = new Audio('/sounds/status.mp3');
                audio.play();
                audio.play();
            }
        }
        accessOffers.value = response.data.offers;
        channelBalances.value = JSON.parse(response.data.adminDashboard.channelBalances);
        // for each

    }).catch(error => {
        console.log(error);
    });
}, 1000);

let tempAdminDashboard = JSON.parse(JSON.stringify(props.adminDashboard));
// convert tempAdminDashboard.payment_methods to array from json string
tempAdminDashboard.payment_methods = JSON.parse(tempAdminDashboard.payment_methods);
tempAdminDashboard.payment_currencies = JSON.parse(tempAdminDashboard.payment_currencies);


const clicked = () => {
    axios.post(route('updateAdminDashboard'), {
        adminDashboard: tempAdminDashboard
    }).then(response => {
        console.log(response.data);
    }).catch(error => {
        console.log(error);
    });
}

const panicButtonToggle = () => {
    tempAdminDashboard.panicButton = !tempAdminDashboard.panicButton;
    console.log('panic button toggled');
    clicked();
}


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
            <button
                v-on:click="panicButtonToggle"
                class=" text-white font-bold py-2 px-4 border-b-4 rounded"
                :class="tempAdminDashboard.panicButton ?
                'bg-red-500 hover:bg-red-400 border-red-700 hover:border-red-500' :
                'bg-zinc-500 hover:bg-zinc-400 border-zinc-700 hover:border-zinc-500'">

                Panic Button {{ tempAdminDashboard.panicButton ? 'ON' : 'OFF' }}
            </button>
        </div>

        <div v-if="tempAdminDashboard" class="flex flex-row mx-auto gap-x-5 my-5 mt-10 item s-center justify-center">
            <div class="text-left border-r border-black dark:border-white/70 pr-5">
                <p class=""><span class="font-bold text-xl mb-2">Wallet:</span></p>

                <p class=""><span class="font-bold">Lighting Wallet Balance:</span> {{ tempAdminDashboard.localBalance }} </p>
                <p class=""><span class="font-bold">Remote Balance:</span> {{ tempAdminDashboard.remoteBalance }} </p>
                <p class=""><span class="font-bold">Auto Topup:</span>     <ToggleButton v-model="tempAdminDashboard.autoTopup" size="sm" activeColor="bg-green-500" inactiveColor="bg-red-500" /></p>
                <div class="border-b border-zinc-300 dark:border-zinc-700"></div>
                <div class="flex flex-col gap-y-1 text-xs pt-2">
                    <div v-for="channelBalance in channelBalances"
                        class="flex flex-row gap-x-2">
                        <span class="font-bold"> {{ channelBalance.channelName }}: </span>
                        <span class="font-bold text-green-500"> {{ channelBalance.localBalance }} </span>
                        <span class="font-bold text-red-500"> {{ channelBalance.remoteBalance }} </span>
                    </div>
                </div>
            </div>
            <div class="text-left border-r border-black dark:border-white/70 pr-5">
                <p class=""><span class="font-bold text-xl mb-2">Automation:</span></p>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Auto Accept</span><ToggleButton v-model="tempAdminDashboard.autoAccept" size="sm" activeColor="bg-green-500" inactiveColor="bg-red-500" /></div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Auto Bond</span><ToggleButton v-model="tempAdminDashboard.autoBond" size="sm" activeColor="bg-green-500" inactiveColor="bg-red-500" /></div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Auto Escrow</span><ToggleButton v-model="tempAdminDashboard.autoEscrow" size="sm" activeColor="bg-green-500" inactiveColor="bg-red-500" /></div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Auto Chat</span><ToggleButton v-model="tempAdminDashboard.autoMessage" size="sm" activeColor="bg-green-500" inactiveColor="bg-red-500" /></div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Auto Confirm</span><ToggleButton v-model="tempAdminDashboard.autoConfirm" size="sm" activeColor="bg-green-500" inactiveColor="bg-red-500" /></div>
            </div>
            <div class="text-left pl-5 flex flex-col gap-y-1 border-r border-black dark:border-white/70 pr-5">
                <div class="flex flex-row justify-between items-center"><span class="font-bold text-xl mb-2">Offer Selection:</span></div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Sell Premium: </span><TextInput v-model="tempAdminDashboard.sell_premium" /></div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Buy Premium: </span><TextInput v-model="tempAdminDashboard.buy_premium"/></div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Concurrent Transactions: </span><TextInput v-model="tempAdminDashboard.max_concurrent_transactions" /></div>
                <div class="border-b border-zinc-300 dark:border-zinc-700"></div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Min Sat Profit: </span><TextInput v-model="tempAdminDashboard.min_satoshi_profit" /></div>
                <p class="text-xs w-96">This is for the auto accept feature. If the profit is less than this value, the offer will not be accepted.</p>
            </div>
            <div class="text-left pl-5 flex flex-col gap-y-1 border-r border-black dark:border-white/70 pr-5">
                <div class="flex flex-row justify-between items-center"><span class="font-bold text-xl mb-2">More Config:</span></div>
                <div class="grid-cols-2 grid gap-2">
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Umbrel Token: </span>
                        <TextInput v-model="tempAdminDashboard.umbrel_token"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Revolut Tag: </span>
                        <TextInput v-model="tempAdminDashboard.revolut_handle"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Paypal Tag: </span>
                        <TextInput v-model="tempAdminDashboard.paypal_handle"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Wise Tag: </span>
                        <TextInput v-model="tempAdminDashboard.wise_handle"/>
                    </div>
                </div>
                <div class="flex flex-row gap-x-4 justify-between">
                    <PaymentsInput :payment_methods="tempAdminDashboard.payment_methods"
                                   @update:model-value="tempAdminDashboard.payment_methods = $event"/>
                    <primary-button class="h-12 mt-5" @click="clicked">Save Changes</primary-button>
                </div>
                <CurrenciesInput :payment_methods="tempAdminDashboard.payment_currencies"
                               @update:model-value="tempAdminDashboard.payment_currencies = $event"/>

            </div>
            <div class="text-left pl-5 flex flex-col gap-y-1 border-r border-zinc-300 dark:border-white/70 pr-5">
                <div class="flex flex-row justify-between items-center"><span class="font-bold text-xl mb-2">Statistics (satoshies):</span></div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Profit: </span><span v-text="tempAdminDashboard.satoshi_profit"/> </div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Fees: </span><span v-text="tempAdminDashboard.satoshi_fees"/> </div>
                <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Trade Volume: </span><span v-text="tempAdminDashboard.trade_volume_satoshis"/> </div>
            </div>


        </div>


        <div
            class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white"
        >
            <div class="relative w-full  mx-16 px-6 l">

                <main class=" ">
                    <div class="grid grid-cols-5 gap-6  mx-auto" v-if="accessOffers.length > 0">
                        <Offer v-for="offer in accessOffers" :key="offer.robosatsId" :offer="offer" />
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
