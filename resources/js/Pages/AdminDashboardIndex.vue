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

                <div class=" min-h-screen flex flex-row w-screen">

                    <div v-if="tempAdminDashboard" class="mx-auto gap-5 gap-x-5 py-5 pt-10 item s-center justify-center">

                        <div class="text-left pl-5 flex flex-col gap-y-1 pr-5 mx-auto">
                            <primary-button class="h-12 mt-5 w-20 te" @click="router.visit(route('welcome'))">
                                <p class="mx-auto">Back</p>
                            </primary-button>
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
                    </div>
                </div>
</template>
