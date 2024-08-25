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

const props = defineProps({
    offers: Array,
    btcPrices: Object,
    adminDashboard: Object,
});

const accessOffers = ref(props.offers);
const channelBalances = ref(JSON.parse(props.adminDashboard.channelBalances));
const refreshKey = ref(0);

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
            if (response.data.offers[i].transaction !== null && response.data.offers[i].transaction !== undefined &&
                accessOffers.value[i] !== undefined && accessOffers.value[i] !== null &&
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
        // tempAdminDashboard.value = response.data.adminDashboard;
        channelBalances.value = JSON.parse(response.data.adminDashboard.channelBalances);
        refreshKey.value += 1;
        // for each

    }).catch(error => {
        console.log(error);
    });
}, 15000);

let tempAdminDashboard = ref(JSON.parse(JSON.stringify(props.adminDashboard)));
// convert tempAdminDashboard.payment_methods to array from json string
tempAdminDashboard.value.payment_methods = JSON.parse(tempAdminDashboard.value.payment_methods);
tempAdminDashboard.value.payment_currencies = JSON.parse(tempAdminDashboard.value.payment_currencies);


const clicked = () => {
    axios.post(route('updateAdminDashboard'), {
        adminDashboard: tempAdminDashboard.value,
    }).then(response => {
        console.log(response.data);
    }).catch(error => {
        console.log(error);
    });

    setTimeout(() => {
        router.reload()
    }, 500);
}

const panicButtonToggle = () => {
    tempAdminDashboard.value.panicButton = !tempAdminDashboard.value.panicButton;
    console.log('panic button toggled');
    clicked();
    setTimeout(() => {
        router.reload()
    }, 500);
}

const showSidebar = ref(true);

</script>

<template>
    <Head title="Offers" />
    <div class="min-h-screen">

        <p class="font-bold text-2xl mx-auto text-center py-5">Lightning Arbitrage Solutions</p>

        <div class="w-full flex flex-row gap-x-8 mx-auto  justify-center ">
            <div v-if="btcPrices.length > 0" v-for="btcPrice in btcPrices" :key="btcPrice.id">
                <div class="text-center">
                    <span class="text-md font-bold">{{ btcPrice.currency }}</span>
                </div>
                <div class="text-center">
                    <span class="text-md font-bold">{{ btcPrice.price }}</span>
                </div>
            </div>
            <div v-else>
                <p>Loading BTC prices...</p>
            </div>
        </div>
        <div class="w-full flex flex-row flex-wrap gap-3 mt-2 mx-auto justify-center">
            <primary-button class="h-12" @click="showSidebar = !showSidebar" v-text="showSidebar ? 'Hide Sidebar' : 'Show Sidebar'"></primary-button>

            <danger-button
                v-on:click="panicButtonToggle"
                class="h-12 text-xs text-white font-bold py-2 px-4 rounded"
                :class="tempAdminDashboard.panicButton ?
            'bg-red-500 hover:bg-red-400 border-red-800 hover:border-red-600' :
            'bg-zinc-500 hover:bg-zinc-400 !border-zinc-600 hover:border-zinc-500'">

                Panic Button {{ tempAdminDashboard.panicButton ? 'ON' : 'OFF' }}
            </danger-button>
            <Link :href="route('dashboard.index')">
                <secondary-button  class="h-12">
                    Config
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
            <Link :href="route('transactions.index')" >
                <secondary-button class="h-12">
                    Transactions
                </secondary-button>
            </Link>
            <Link :href="route('payments.index')" >
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
        </div>
        <div class="my-5 border-b-2 border-gray-300 dark:border-zinc-700"></div>

        <div v-if="tempAdminDashboard" class="w-screen flex flex-row mx-auto px-10 my-5 mt-2 item s-center justify-center">

            <div v-if="showSidebar" :key="refreshKey"
                class="flex flex-row gap-x-2 h-full  pr-2">
                <div class="flex flex-col text-left ">

                    <p class=""><span class="font-bold text-xl mb-2">Automation:</span></p>
                    <div class="border-b border-zinc-300 dark:border-zinc-700 my-1"></div>

                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Auto Accept</span>
                        <ToggleButton v-model="tempAdminDashboard.autoAccept" size="sm" activeColor="bg-green-500"
                                      inactiveColor="bg-red-500"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Auto Bond</span>
                        <ToggleButton v-model="tempAdminDashboard.autoBond" size="sm" activeColor="bg-green-500"
                                      inactiveColor="bg-red-500"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Auto Escrow</span>
                        <ToggleButton v-model="tempAdminDashboard.autoEscrow" size="sm" activeColor="bg-green-500"
                                      inactiveColor="bg-red-500"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Auto Chat</span>
                        <ToggleButton v-model="tempAdminDashboard.autoMessage" size="sm" activeColor="bg-green-500"
                                      inactiveColor="bg-red-500"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Auto Confirm</span>
                        <ToggleButton v-model="tempAdminDashboard.autoConfirm" size="sm" activeColor="bg-green-500"
                                      inactiveColor="bg-red-500"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Auto Topup:</span>
                        <ToggleButton v-model="tempAdminDashboard.autoTopup" size="sm" activeColor="bg-green-500"
                                      inactiveColor="bg-red-500"/>
                    </div>

                    <div class="flex flex-row justify-between items-center"><span class="font-bold text-xl mt-2">Offer Selection:</span>
                    </div>
                    <div class="border-b border-zinc-300 dark:border-zinc-700 my-1"></div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Sell Premium: </span>
                        <TextInput class="w-16 h-6" v-model="tempAdminDashboard.sell_premium"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Sitting Sell Offers Count: </span>
                        <TextInput class="w-16 h-6" v-model="tempAdminDashboard.sitting_sell_offers_count"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Sitting Sell Offers Min Premium: </span>
                        <TextInput class="w-16 h-6" v-model="tempAdminDashboard.sitting_sell_offers_min_premium"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Sitting Sell Offers Max Premium: </span>
                        <TextInput class="w-16 h-6" v-model="tempAdminDashboard.sitting_sell_offers_max_premium"/>
                    </div>
                    <!--<div class="flex flex-row justify-between items-center"><span-->
                    <!--    class="font-bold mr-1">Buy Premium: </span>-->
                    <!--    <TextInput class="w-16 h-6" v-model="tempAdminDashboard.buy_premium"/>-->
                    <!--</div>-->
                    <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Concurrent Transactions: </span>
                        <TextInput class="w-16 h-6" v-model="tempAdminDashboard.max_concurrent_transactions"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Min Sat Profit: </span>
                        <TextInput class="w-36 h-6" v-model="tempAdminDashboard.min_satoshi_profit"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Max Sat Amount: </span>
                        <TextInput class="w-36 h-6" v-model="tempAdminDashboard.max_satoshi_amount"/>
                    </div>

                    <primary-button class="h-8 mt-4 mx-auto" @click="clicked">Save Changes</primary-button>


                    <div class="flex flex-col gap-y-3 border-t-2 border-zinc-300 dark:border-white/70 mt-4 pt-1">

                        <div class="text-left  ">
                            <p class=""><span class="font-bold text-xl mb-2">Wallet:</span></p>

                            <p class=""><span class="font-bold">Lighting Wallet Balance:</span> {{ tempAdminDashboard.localBalance }} </p>
                            <!--<p class=""><span class="font-bold">Revolut Balance:</span></p>-->
                            <!--<p class=""><span class="font-bold">Wise Balance:</span></p>-->
                            <!--<p class=""><span class="font-bold">Kraken Balance:</span></p>-->
                            <p class=""><span class="font-bold">Remote Balance:</span> {{ tempAdminDashboard.remoteBalance }} </p>
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
                    </div>

                    <div class="flex flex-col gap-y-3 border-t-2 border-zinc-300 dark:border-white/70 mt-4 pt-1">
                        <div class="text-left flex flex-col gap-y-1 ">
                            <div class="flex flex-row justify-between items-center"><span class="font-bold text-xl mb-2">Statistics (satoshies):</span>
                            </div>
                            <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Profit: </span><span
                                v-text="tempAdminDashboard.satoshi_profit"/></div>
                            <div class="flex flex-row justify-between items-center"><span
                                class="font-bold mr-1">Fees: </span><span v-text="tempAdminDashboard.satoshi_fees"/></div>
                            <div class="flex flex-row justify-between items-center"><span
                                class="font-bold mr-1">Trade Volume: </span><span
                                v-text="tempAdminDashboard.trade_volume_satoshis"/></div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="relative flex flex-col flex-grow items-center selection:bg-[#FF2D20] selection:text-white"
                v-bind:class="showSidebar ? 'border-l-2 dark:border-zinc-700 dark:border-white/70' : ''">
                <div class="grid gap-6 gap-x-4 mx-auto px-2" v-if="accessOffers.length > 0"
                     v-bind:class="showSidebar ? 'grid-cols-2' : 'grid-cols-3'">
                    <Offer v-for="offer in accessOffers" :key="offer.robosatsId" :offer="offer"
                        :showSidebar="showSidebar"/>
                </div>
                <div class="mx-auto" v-else>
                    <p class="text-lg">No offers available</p>
                </div>
            </div>



        </div>


    </div>
</template>
