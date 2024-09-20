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
import ToastList from "@/Components/Toast/ToastList.vue";
import ConfirmModal from "@/Modals/ConfirmModal.vue";
import toastStore from "@/Stores/ToastStore.js";

const props = defineProps({
    offers: Array,
    btcPrices: Object,
    adminDashboard: Object,
	satsInTransit: Number,
});

const accessOffers = ref(props.offers);
const channelBalances = ref(JSON.parse(props.adminDashboard.channelBalances));
const refreshKey = ref(0);
const sellOffers = ref([]);
const buyOffers = ref([]);

const setBuyAndSellOffers = () => {
	sellOffers.value = [];
	buyOffers.value = [];
	for (let i = 0; i < accessOffers.value.length; i++) {
		if (accessOffers.value[i].type === 'sell') {
			sellOffers.value.push(accessOffers.value[i]);
		} else {
			buyOffers.value.push(accessOffers.value[i]);
		}
	}
}
setBuyAndSellOffers();

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
		setBuyAndSellOffers();
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
tempAdminDashboard.value.provider_statuses = JSON.parse(tempAdminDashboard.value.provider_statuses);
// reverse provider statuses
if (tempAdminDashboard.value.provider_statuses !== null) {
    tempAdminDashboard.value.provider_statuses = Object.fromEntries(Object.entries(tempAdminDashboard.value.provider_statuses).reverse());
}

const clicked = () => {
    axios.post(route('updateAdminDashboard'), {
        adminDashboard: tempAdminDashboard.value,
    }).then(response => {
        console.log(response.data);
		toastStore.add({
			message: 'Admin Dashboard updated',
			type: "success",
		});
    }).catch(error => {
        console.log(error);
		toastStore.add({
			message: 'Error updating Admin Dashboard',
			type: "error",
		});
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
	<ConfirmModal />
	<ToastList/>
    <div class="min-h-screen">

        <div class="flex flex-col ">
            <a href="https://www.lightningarbitragesolutions.com"
			   class="font-bold text-2xl mx-auto text-center pt-5 ">
				<!--Lightning Arbitrage Solutions-->
				<!--<img src="/images/logo.png" alt="Lightning Arbitrage Solutions" class="w-32 mx-auto"/>-->
				<img src="/images/logoLight.png" alt="Lightning Arbitrage Solutions" class="w-72 mx-auto dark:hidden"/>
				<img src="/images/logoDark.png" alt="Lightning Arbitrage Solutions" class="w-72 mx-auto hidden dark:block"/>
				
			</a>
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
        </div>
        <!--<div class="flex flex-row gap-x-2 absolute top-0 w-full">-->
        <!--    <div class="flex flex-col flex-grow opacity-0 h-20">-->
        <!--    </div>-->
		
        <!--    <div class="flex-shrink-0 my-auto mx-10">-->
        <!--        &lt;!&ndash;<Link :href="route('simple')">&ndash;&gt;-->
        <!--        &lt;!&ndash;    <secondary-button class="h-10 my-auto">&ndash;&gt;-->
        <!--        &lt;!&ndash;        Simple&ndash;&gt;-->
        <!--        &lt;!&ndash;    </secondary-button>&ndash;&gt;-->
        <!--        &lt;!&ndash;</Link>&ndash;&gt;-->
        <!--    </div>-->
        <!--</div>-->


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
                <primary-button  class="h-12">
                    Config
                </primary-button>
            </Link>
            <Link :href="route('offers.completed')" >
                <primary-button class="h-12">
                    Offer Management
                </primary-button>
            </Link>

            <Link :href="route('offers.posting.index')" >
                <primary-button class="h-12">
                    Offer Templates
                </primary-button>
            </Link>
            <Link :href="route('transactions.index')" >
                <primary-button class="h-12">
                    Transactions
                </primary-button>
            </Link>
            <Link :href="route('payments.index')" class="opacity-50 pointer-events-none">
                <primary-button class="h-12">
                    Payments
                </primary-button>
            </Link>
            <Link :href="route('purchases.index')" >
                <primary-button class="h-12">
                    Bitcoin Purchases
                </primary-button>
            </Link>
            <Link :href="route('graphs.index')" >
                <primary-button class="h-12">
                    Graphs
                </primary-button>
            </Link>
            <Link :href="route('graphs.index')" >
                <primary-button class="h-12">
                    Tabulated Data
                </primary-button>
            </Link>
        </div>
        <div class="my-5 border-b-2 border-gray-300 dark:border-zinc-700"></div>

        <div v-if="tempAdminDashboard" class="w-screen flex flex-row mx-auto px-10 my-5 mt-2 item s-center justify-center">

            <div v-if="showSidebar" :key="refreshKey"
                class="flex flex-row gap-x-2 h-full  pr-2">
                <div class="flex flex-col text-left ">

                    <p class=""><span class="font-bold text-xl mb-2">Automation:</span></p>
                    <div class="border-b border-zinc-300 dark:border-zinc-700 my-1"></div>

                    <!--<div class="flex flex-row justify-between items-center"><span-->
                    <!--    class="font-bold mr-1">Auto Reward Collection</span>-->
                    <!--    <ToggleButton v-model="tempAdminDashboard.autoReward" size="sm" activeColor="bg-green-500"-->
                    <!--                  inactiveColor="bg-red-500"/>-->
                    <!--</div>-->
					<div class="flex flex-row justify-between items-center"><span
					  class="font-bold mr-1">Auto Reward</span>
						<ToggleButton v-model="tempAdminDashboard.autoReward" size="sm" activeColor="bg-green-500"
									  inactiveColor="bg-red-500"/>
					</div>
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
					  class="font-bold mr-1">Auto Invoice</span>
						<ToggleButton v-model="tempAdminDashboard.autoInvoice" size="sm" activeColor="bg-green-500"
									  inactiveColor="bg-red-500"/>
					</div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Auto Chat</span>
                        <ToggleButton v-model="tempAdminDashboard.autoMessage" size="sm" activeColor="bg-green-500"
                                      inactiveColor="bg-red-500"/>
                    </div>
                    <div class="flex flex-row justify-between items-center opacity-50 pointer-events-none"><span
                        class="font-bold mr-1">Auto Confirm</span>
                        <ToggleButton v-model="tempAdminDashboard.autoConfirm" size="sm" activeColor="bg-green-500"
                                      inactiveColor="bg-red-500"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Kraken Auto Topup:</span>
                        <ToggleButton v-model="tempAdminDashboard.autoTopup" size="sm" activeColor="bg-green-500"
                                      inactiveColor="bg-red-500"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Auto Create:</span>
                        <ToggleButton v-model="tempAdminDashboard.autoCreate" size="sm" activeColor="bg-green-500"
                                      inactiveColor="bg-red-500"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Scheduler:</span>
                        <ToggleButton v-model="tempAdminDashboard.scheduler" size="sm" activeColor="bg-green-500"
                                      inactiveColor="bg-red-500"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Start Time:</span>
                        <input type="time" v-model="tempAdminDashboard.auto_accept_start_time" class="bg-zinc-100 dark:bg-zinc-700 text-black dark:text-white"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">End Time:</span>
                        <input type="time" v-model="tempAdminDashboard.auto_accept_end_time" class="bg-zinc-100 dark:bg-zinc-700 text-black dark:text-white"/>

                    </div>

                    <div class="flex flex-row justify-between items-center"><span class="font-bold text-xl mt-2">Offer Selection:</span>
                    </div>
                    <div class="border-b border-zinc-300 dark:border-zinc-700 my-1"></div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Sell Premium: </span>
                        <TextInput class="w-16 h-6" v-model="tempAdminDashboard.sell_premium"/>
                    </div>
                    <!--<div class="flex flex-row justify-between items-center"><span-->
                    <!--    class="font-bold mr-1">Sitting Sell Offers Count: </span>-->
                    <!--    <TextInput class="w-16 h-6" v-model="tempAdminDashboard.sitting_sell_offers_count"/>-->
                    <!--</div>-->
                    <!--<div class="flex flex-row justify-between items-center"><span-->
                    <!--    class="font-bold mr-1">Sitting Sell Offers Min Premium: </span>-->
                    <!--    <TextInput class="w-16 h-6" v-model="tempAdminDashboard.sitting_sell_offers_min_premium"/>-->
                    <!--</div>-->
                    <!--<div class="flex flex-row justify-between items-center"><span-->
                    <!--    class="font-bold mr-1">Sitting Sell Offers Max Premium: </span>-->
                    <!--    <TextInput class="w-16 h-6" v-model="tempAdminDashboard.sitting_sell_offers_max_premium"/>-->
                    <!--</div>-->
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Buy Premium: </span>
                        <TextInput class="w-16 h-6" v-model="tempAdminDashboard.buy_premium"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Active Accepting Offers Limit: </span>
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
                            <label for="ideal_lightning_node_balance">Ideal Lightning Node Balance:</label>
                            <TextInput class="w-36 h-6" v-model="tempAdminDashboard.ideal_lightning_node_balance"/>

                            <p class=""><span class="font-bold">Lighting Wallet Balance:</span> {{ tempAdminDashboard.localBalance }} </p>
                            <!--<p class=""><span class="font-bold">Revolut Balance:</span></p>-->
                            <!--<p class=""><span class="font-bold">Wise Balance:</span></p>-->
							<p class=""><span class="font-bold">Kraken Balance:</span> {{ tempAdminDashboard.kraken_btc_balance }} </p>
							<p class=""><span class="font-bold">Bond & Escrow Balance:</span> {{ satsInTransit }} </p>
							
                            <p class=""><span class="font-bold">Remote Balance:</span> {{ tempAdminDashboard.remoteBalance }} </p>
                            <div class="border-b border-zinc-300 dark:border-zinc-700"></div>
                            <div v-if="channelBalances.length > 0"
                                class="flex flex-col gap-y-1 text-xs pt-2">
                                <div v-for="channelBalance in channelBalances"
                                     class="flex flex-row gap-x-2">
                                    <span class="font-bold"> {{ channelBalance.channelName }}: </span>
                                    <span class="font-bold text-green-500"> {{ channelBalance.localBalance }} </span>
                                    <span class="font-bold text-red-500"> {{ channelBalance.remoteBalance }} </span>
                                </div>
                            </div>
                            <div v-else class="mt-3 text-xs ">
                                <p class="text-center mx-2">No channel balances available</p>
                            </div>
                        </div>
                    </div>

                    <!--<div class="flex flex-col gap-y-3 border-t-2 border-zinc-300 dark:border-white/70 mt-4 pt-1">-->
                    <!--    <div class="text-left flex flex-col gap-y-1 ">-->
                    <!--        <div class="flex flex-row justify-between items-center"><span class="font-bold text-xl mb-2">Statistics (satoshies):</span>-->
                    <!--        </div>-->
                    <!--        <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Profit: </span><span-->
                    <!--            v-text="tempAdminDashboard.satoshi_profit"/></div>-->
                    <!--        <div class="flex flex-row justify-between items-center"><span-->
                    <!--            class="font-bold mr-1">Fees: </span><span v-text="tempAdminDashboard.satoshi_fees"/></div>-->
                    <!--        <div class="flex flex-row justify-between items-center"><span-->
                    <!--            class="font-bold mr-1">Trade Volume: </span><span-->
                    <!--            v-text="tempAdminDashboard.trade_volume_satoshis"/></div>-->
                    <!--    </div>-->
                    <!--</div>-->

                    <div class="flex flex-col gap-y-3 border-t-2 border-zinc-300 dark:border-white/70 mt-4 pt-1">
                        <div class="text-left flex flex-col gap-y-1 ">
                                <span class="font-bold text-xl mb-2">Provider Statuses:</span>
                                <div class="flex flex-col gap-y-2">
                                    <div  v-if="tempAdminDashboard.provider_statuses !== null"
									  	v-for="(value, key) in tempAdminDashboard.provider_statuses"
                                         class="flex flex-col gap-y-2">
                                        <div class="flex flex-row gap-x-2 font-bold">
                                            <span class=""> {{ key }}: </span>
                                            <!--if value is false then show a red dot-->
                                            <div v-if="value === false" class="flex flex-row gap-x-2">
                                                <p>Offline</p>
                                                <div class="bg-red-500 w-4 h-4 rounded-full my-auto"></div>
                                            </div>
                                            <div v-if="value !== false" class="flex flex-row gap-x-2">
                                                <p>Online</p>
                                                <div class="bg-green-500 w-4 h-4 rounded-full my-auto"></div>
                                            </div>

                                        </div>
                                    </div>
									<div v-else class="text-xs mx-auto">
										<p>No provider statuses available</p>
									</div>
                                </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="relative flex flex-col flex-grow items-center selection:bg-[#FF2D20] selection:text-white"
                v-bind:class="showSidebar ? 'border-l-2 dark:border-zinc-700 dark:border-white/70' : ''">
                <div class="grid grid-cols-2 gap-6 gap-x-4 w-full px-2" v-if="accessOffers.length > 0">
					<div class="flex flex-col w-full ">
						<Offer v-for="offer in sellOffers"
							   :offer="offer"
							   :key="offer.robosatsId"
							:showSidebar="showSidebar"/>
					</div>
					<div class="flex flex-col w-full">
						<Offer v-for="offer in buyOffers"
							   :offer="offer"
							   :key="offer.robosatsId"
							:showSidebar="showSidebar"/>
					</div>
				
                </div>
                <div class="mx-auto" v-else>
                    <p class="text-lg">No offers available</p>
                </div>
            </div>



        </div>


    </div>
</template>
