<script setup>
import {Head, Link, router} from '@inertiajs/vue3';
import Offer from "@/Components/Offer.vue";
import ToggleButton from "@/Components/ToggleButton.vue";
import {onMounted, ref, watch} from "vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import PaymentsInput from "@/Components/PaymentsInput.vue";
import CurrenciesInput from "@/Components/CurrenciesInput.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    btcPrices: Object,
    adminDashboard: Object,
});

const channelBalances = ref(JSON.parse(props.adminDashboard.channelBalances));




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
    <Head title="Config" />

                <div class="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50 min-h-screen">

                    <div v-if="tempAdminDashboard" class="grid-cols-2 grid gap-5 mx-auto gap-x-5 py-5 pt-2 item s-center justify-center">
                        <div class="text-left pl-5 flex flex-col gap-y-1 border-r border-black dark:border-white/70 pr-5">
                            <div class="flex flex-row justify-between items-center"><span class="font-bold text-xl mb-2">Offer Selection:</span></div>
                            <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Sell Premium: </span><TextInput v-model="tempAdminDashboard.sell_premium" /></div>
                            <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Buy Premium: </span><TextInput v-model="tempAdminDashboard.buy_premium"/></div>
                            <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Concurrent Transactions: </span><TextInput v-model="tempAdminDashboard.max_concurrent_transactions" /></div>
                            <div class="border-b border-zinc-300 dark:border-zinc-700"></div>
                            <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Min Sat Profit: </span><TextInput v-model="tempAdminDashboard.min_satoshi_profit" /></div>
                            <p class="text-xs w-96">This is for the auto accept feature. If the profit is less than this value, the offer will not be accepted.</p>
                            <div class="flex flex-row justify-between items-center"><span class="font-bold mr-1">Max Sat Amount: </span><TextInput v-model="tempAdminDashboard.max_satoshi_amount" /></div>

                        </div>
                        <div class="text-left pl-5 flex flex-col gap-y-1 pr-5">
                            <div class="flex flex-row justify-between items-center"><span class="font-bold text-xl mb-2">More Config:</span></div>
                            <div class="grid-cols-1 grid gap-2">
                                <div class="flex flex-row justify-between items-center"><span
                                    class="font-bold mr-1">Umbrel Token: </span>
                                    <TextInput v-model="tempAdminDashboard.umbrel_token"/>
                                </div>
                                <div class="flex flex-row justify-between items-center"><span
                                    class="font-bold mr-1">Rev Token: </span>
                                    <TextInput v-model="tempAdminDashboard.revolut_code"/>
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

                        <div class="flex flex-col gap-y-3 pl-5 border-r border-t border-zinc-300 dark:border-white/70  pr-5">
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
                        <div class="flex flex-col gap-y-3 pl-5 border-t border-r border-zinc-300 dark:border-white/70  pr-5">

                            <div class="text-left  ">
                                <p class=""><span class="font-bold text-xl mb-2">Wallet:</span></p>

                                <p class=""><span class="font-bold">Lighting Wallet Balance:</span> {{ tempAdminDashboard.localBalance }} </p>
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
                    </div>
                </div>
</template>
