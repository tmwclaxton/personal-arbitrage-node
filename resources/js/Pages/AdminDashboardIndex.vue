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
import GuestLayout from "@/Layouts/GuestLayout.vue";
import PaymentMethod from "@/Components/PaymentMethod.vue";

const props = defineProps({
    adminDashboard: Object,
    currencies: Array,
    paymentMethods: Object,
    paymentMethodList: Array,
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

const paymentMethodNew = ref({
    name: '',
    handle: '',
    logo_url: '',
    specific_buy_premium: '',
    specific_sell_premium: '',
});

const addPaymentMethod = () => {
    axios.post(route('add-payment-method'), {
        name: paymentMethodNew.value.name,
        handle: paymentMethodNew.value.handle,
        logo_url: paymentMethodNew.value.logo_url,
        specific_buy_premium: paymentMethodNew.value.specific_buy_premium,
        specific_sell_premium: paymentMethodNew.value.specific_sell_premium,
    }).then(response => {
        console.log(response.data);
    }).catch(error => {
        console.log(error);
    });
}

const showAddPaymentMethod = ref(false);

</script>


<template>
    <Head title="Config" />

    <guest-layout>
        <div class=" min-h-screen flex flex-row w-screen">

            <div v-if="tempAdminDashboard" class="mx-auto gap-5 gap-x-5  item s-center justify-center">

                <div class="text-left pl-5 flex flex-col gap-y-1 pr-5 mx-auto">

                    <div class="flex flex-row justify-between items-center">
                        <span class="font-bold text-xl mb-2">More Config:</span>
                    </div>
                    <div class="grid-cols-1 grid gap-2">
                        <!--<div class="flex flex-row justify-between items-center"><span-->
                        <!--    class="font-bold mr-1">Umbrel Token: </span>-->
                        <!--    <TextInput v-model="tempAdminDashboard.umbrel_token"/>-->
                        <!--</div>-->
                        <!--<div class="flex flex-row justify-between items-center"><span-->
                        <!--    class="font-bold mr-1">Revolut Tag: </span>-->
                        <!--    <TextInput v-model="tempAdminDashboard.revolut_handle"/>-->
                        <!--</div>-->
                        <!--<div class="flex flex-row justify-between items-center"><span-->
                        <!--    class="font-bold mr-1">Paypal Tag: </span>-->
                        <!--    <TextInput v-model="tempAdminDashboard.paypal_handle"/>-->
                        <!--</div>-->
                        <!--<div class="flex flex-row justify-between items-center"><span-->
                        <!--    class="font-bold mr-1">Wise Tag: </span>-->
                        <!--    <TextInput v-model="tempAdminDashboard.wise_handle"/>-->
                        <!--</div>-->
                        <!--<div class="flex flex-row justify-between items-center"><span-->
                        <!--    class="font-bold mr-1">Strike Tag: </span>-->
                        <!--    <TextInput v-model="tempAdminDashboard.strike_handle"/>-->
                        <!--</div>-->
                        <!--<div class="flex flex-row justify-between items-center"><span-->
                        <!--    class="font-bold mr-1 flex-shrink-0">Instant Sepa Info: </span>-->
                        <!--    <TextInput class="w-full text-right"  v-model="tempAdminDashboard.instant_sepa"/>-->
                        <!--</div>-->
                        <!--<div class="flex flex-row justify-between items-center"><span-->
                        <!--    class="font-bold mr-1 flex-shrink-0">Faster Payments Info: </span>-->
                        <!--    <TextInput class="w-full text-right" v-model="tempAdminDashboard.faster_payments"/>-->
                        <!--</div>-->


                        <PaymentMethod  v-for="paymentMethod in props.paymentMethods"
                                        :paymentMethod="paymentMethod"
                                        @update:model-value="paymentMethod = $event"
                                        :key="paymentMethod.id"/>

                        <!--- add new payment methods here -->
                        <div class="flex flex-col justify-between border-t border-b border-gray-300 dark:border-zinc-700 p-2">
                            <primary-button @click="showAddPaymentMethod = !showAddPaymentMethod"
                            class="font-bold mr-1 flex-shrink-0 w-max" v-text="showAddPaymentMethod ? 'Hide' : 'Add New Payment Method:'">
                            </primary-button>
                            <div class="flex flex-col gap-y-2 my-3" v-if="showAddPaymentMethod">
                                    <label for="name">Name</label>
                                <TextInput class="w-full text-left" v-model="paymentMethodNew.name"/>
                                <label for="handle">Handle</label>
                                <TextInput class="w-full text-left" v-model="paymentMethodNew.handle"/>
                                <label for="logo_url">Logo URL</label>
                                <TextInput class="w-full text-left" v-model="paymentMethodNew.logo_url"/>
                                <label for="specific_buy_premium">Specific Buy Premium (Not required)</label>
                                <TextInput class="w-full text-left" v-model="paymentMethodNew.specific_buy_premium"/>
                                <label for="specific_sell_premium">Specific Sell Premium (Not required)</label>
                                <TextInput class="w-full text-left" v-model="paymentMethodNew.specific_sell_premium"/>
                            </div>
                            <PrimaryButton class="mt-2" @click="addPaymentMethod" v-if="showAddPaymentMethod">Add Payment Method</PrimaryButton>
                        </div>


                    </div>
                        <PaymentsInput :payment_methods="tempAdminDashboard.payment_methods"
                                       :options="props.paymentMethodList"
                                       @update:model-value="tempAdminDashboard.payment_methods = $event"/>

                    <div class="flex flex-row gap-x-4 justify-between">
                    <CurrenciesInput class=""
                        :payment_methods="tempAdminDashboard.payment_currencies"
                                     @update:model-value="tempAdminDashboard.payment_currencies = $event"
                                        :currencies="currencies"/>
                        <primary-button class="h-12 mt-5" @click="clicked">Save Changes</primary-button>
                    </div>

                </div>
            </div>
        </div>
    </guest-layout>
</template>
