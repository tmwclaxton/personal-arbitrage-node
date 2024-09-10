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
    custom_message: '',
    ask_for_reference: false,
    allowed_currencies: [],
});

const addPaymentMethod = () => {
    axios.post(route('add-payment-method'), {
        name: paymentMethodNew.value.name,
        handle: paymentMethodNew.value.handle,
        logo_url: paymentMethodNew.value.logo_url,
        specific_buy_premium: paymentMethodNew.value.specific_buy_premium,
        specific_sell_premium: paymentMethodNew.value.specific_sell_premium,
        custom_message: paymentMethodNew.value.custom_message,
        ask_for_reference: paymentMethodNew.value.ask_for_reference,
        allowed_currencies: paymentMethodNew.value.allowed_currencies,
    }).then(response => {
        console.log(response.data);
    }).catch(error => {
        console.log(error);
    });
}

const showAddPaymentMethod = ref(false);

const showAllPaymentMethods = ref(false);
const paymentMethodsSlice = ref(props.paymentMethods.slice(0, 6));

const toggleShowAllPaymentMethods = () => {
    showAllPaymentMethods.value = !showAllPaymentMethods.value;
    paymentMethodsSlice.value = showAllPaymentMethods.value ? props.paymentMethods : props.paymentMethods.slice(0, 6);
}

const refreshKey = ref(0);

</script>


<template>
    <Head title="Config" />

    <guest-layout>
        <div class=" min-h-screen flex flex-row w-screen">

            <div v-if="tempAdminDashboard" class="mx-auto gap-5 gap-x-5  item s-center justify-center">

                <div class="text-left pl-5 flex flex-col gap-y-1 pr-5 mx-auto max-w-6xl">

                    <div class="flex flex-row justify-between items-center">
                        <span class="font-bold text-3xl mx-auto mb-2">Settings</span>
                    </div>
                    <div class="border-b border-gray-300 dark:border-zinc-700 "/>
                    <!--<div class="flex flex-row justify-between items-center"><span-->
                    <!--    class="font-bold mr-1">Umbrel Token: <span class="text-red-500">(Should automatically be set once below values filled in)</span></span>-->
                    <!--    <TextInput v-model="tempAdminDashboard.umbrel_token"/>-->
                    <!--</div>-->
	                
		                
					<div class="mt-5 flex flex-row justify-between items-center">
						<span class="font-bold text-2xl mx-auto mb-2">Umbrel Settings</span>
					</div>
                  
                  
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Umbrel Server IP: <span class="text-red-500"></span></span>
                        <TextInput v-model="tempAdminDashboard.umbrel_ip"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Umbrel Server Password: <span class="text-red-500"></span></span>
                        <TextInput v-model="tempAdminDashboard.umbrel_password"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Umbrel OTP Secret: <span class="text-red-500">(Only if 2FA is set)</span></span>
                        <TextInput v-model="tempAdminDashboard.umbrel_topt_key"/>
                    </div>

                    <div class="border-b border-gray-300 dark:border-zinc-700 "/>
                    
	                
					<div class="mt-5 flex flex-row justify-between items-center">
						<span class="font-bold text-2xl mx-auto mb-2">Kraken Settings
							<span class="text-red-500">(Only fill in if you want to automate rebuying on Kraken)</span>
						</span>
				    </div>
	                
                    <!--kraken details-->
                    <div class="flex flex-row justify-between items-center" :key="refreshKey">
                        <span class="font-bold mr-1">Kraken Auto Topup:
                            <span class="text-red-500" v-text="tempAdminDashboard.autoTopup ? 'Enabled' : 'Disabled'"/>
                        </span>
                      <ToggleButton v-model="tempAdminDashboard.autoTopup" @update:modelValue="refreshKey++"/>
                    </div>
					<div class="flex flex-row justify-between items-center"><span
						class="font-bold mr-1">Kraken API Key: <span class="text-red-500"></span></span>
						<TextInput v-model="tempAdminDashboard.kraken_api_key"/>
					</div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Kraken Private Key: <span class="text-red-500"></span></span>
                        <TextInput v-model="tempAdminDashboard.kraken_private_key"/>
                    </div>
                    <div class="flex flex-row justify-between items-center"><span
                        class="font-bold mr-1">Kraken API Secret: <span class="text-red-500"></span></span>
                        <TextInput v-model="tempAdminDashboard.kraken_api_secret"/>
                    </div>
					<div class="flex flex-row justify-between items-center" :key="refreshKey">
					  <span class="font-bold mr-1">Kraken Action:
						  <span class="text-red-500" v-text="tempAdminDashboard.kraken_action ? 'Auto Buy BTC' : 'Auto Sell BTC'"/>
                      </span>
					 <ToggleButton v-model="tempAdminDashboard.kraken_action" @update:modelValue="refreshKey++"/>
					</div>
                  
                    <div class="border-b border-gray-300 dark:border-zinc-700 mb-4"/>
                  
                    <div class="mt-5 flex flex-row justify-between items-center">
                        <span class="font-bold text-2xl mx-auto mb-2">
							Slack Settings (Or use Internal Messaging)
                          <span class="text-red-500"></span>
                        </span>
                    </div>
					
					<div class="flex flex-row justify-between items-center"><span
						class="font-bold mr-1">Slack App ID: <span class="text-red-500"></span></span>
						<TextInput v-model="tempAdminDashboard.slack_app_id"/>
					</div>
					<div class="flex flex-row justify-between items-center"><span
						class="font-bold mr-1">Slack Client ID: <span class="text-red-500"></span></span>
						<TextInput v-model="tempAdminDashboard.slack_client_id"/>
					</div>
					<div class="flex flex-row justify-between items-center"><span
						class="font-bold mr-1">Slack Client Secret: <span class="text-red-500"></span></span>
						<TextInput v-model="tempAdminDashboard.slack_client_secret"/>
					</div>
					<div class="flex flex-row justify-between items-center"><span
						class="font-bold mr-1">Slack Signing Secret: <span class="text-red-500"></span></span>
						<TextInput v-model="tempAdminDashboard.slack_signing_secret"/>
					</div>
					<div class="flex flex-row justify-between items-center"><span
						class="font-bold mr-1">Slack Bot Token: <span class="text-red-500"></span></span>
						<TextInput v-model="tempAdminDashboard.slack_bot_token"/>
					</div>
					
					<div class="border-b border-gray-300 dark:border-zinc-700 mb-4"/>
					

                    <PaymentsInput :payment_methods="tempAdminDashboard.payment_methods"
                                   :options="props.paymentMethodList"
                                   @update:model-value="tempAdminDashboard.payment_methods = $event"/>

                    <div class="flex flex-row gap-x-4 justify-between">
                        <CurrenciesInput :key="'adminDashboard'"
                                         :payment_methods="tempAdminDashboard.payment_currencies"
                                         @update:model-value="tempAdminDashboard.payment_currencies = $event"
                                         :currencies="currencies"/>
                    </div>
                    <primary-button class="h-12 my-5" @click="clicked">
                       <span class="mx-auto">Save Changes</span>
                    </primary-button>

                    <div class="border-b border-gray-300 dark:border-zinc-700 mb-4"/>
                    <div class="grid-cols-1 grid gap-2 ">
                        <p class="text-2xl font-bold my-4 text-center mx-auto">Set up Payment Handles / Messages:</p>
                        <!--<div class="border-b border-gray-300 dark:border-zinc-700 mb-4"/>-->

                        <PaymentMethod  v-for="paymentMethod in paymentMethodsSlice"
                                        :paymentMethod="paymentMethod"
                                        @update:model-value="paymentMethod = $event"
                                        :currencies="tempAdminDashboard.payment_currencies"
                                        :key="paymentMethod.id"/>

                        <primary-button class="font-bold  flex-shrink-0 w-max mx-auto" @click="toggleShowAllPaymentMethods"
                                        v-text="showAllPaymentMethods ? 'Hide' : 'View all'">
                        </primary-button>

                        <!--- add new payment methods here -->
                        <div class="mb-10 flex flex-col justify-between border-t   p-2">
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
                                <label for="custom_message">Custom Message</label>
                                <TextInput class="w-full text-left" v-model="paymentMethodNew.custom_message"/>
                                <label for="ask_for_reference">Ask for Reference</label>
                                <ToggleButton v-model="paymentMethodNew.ask_for_reference"/>
                                <label for="allowed_currencies">Currencies (Not required)</label>
                                <CurrenciesInput v-model="paymentMethodNew.allowed_currencies" :currencies="tempAdminDashboard.payment_currencies"
                                                 :key="'new'"/>
                            </div>
                            <PrimaryButton class="mt-2 mb-10" @click="addPaymentMethod" v-if="showAddPaymentMethod">Add Payment Method</PrimaryButton>
                        </div>
                    </div>


                </div>
				</div>
        </div>
    </guest-layout>
</template>
