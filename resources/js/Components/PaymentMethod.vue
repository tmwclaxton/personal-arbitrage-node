<script setup>

import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import {ref} from "vue";
import ToggleButton from "@/Components/ToggleButton.vue";
import CurrenciesInput from "@/Components/CurrenciesInput.vue";

const props = defineProps({
    paymentMethod: Object,
    currencies: Array,
});

const tempPaymentMethod = ref({
    name: props.paymentMethod.name,
    handle: props.paymentMethod.handle,
    logo_url: props.paymentMethod.logo_url,
    specific_buy_premium: props.paymentMethod.specific_buy_premium,
    specific_sell_premium: props.paymentMethod.specific_sell_premium,
    allowed_currencies: props.paymentMethod.allowed_currencies,
	custom_buy_message: props.paymentMethod.custom_buy_message,
	custom_sell_message: props.paymentMethod.custom_sell_message,
	preference: props.paymentMethod.preference,
});

tempPaymentMethod.value.allowed_currencies = JSON.parse(tempPaymentMethod.value.allowed_currencies);


const editPaymentMethod = () => {
    axios.post(route('update-payment-method', {id: props.paymentMethod.id}), {
        name: tempPaymentMethod.value.name,
        handle: tempPaymentMethod.value.handle,
        logo_url: tempPaymentMethod.value.logo_url,
        specific_buy_premium: tempPaymentMethod.value.specific_buy_premium,
        specific_sell_premium: tempPaymentMethod.value.specific_sell_premium,
        allowed_currencies: tempPaymentMethod.value.allowed_currencies,
		custom_buy_message: tempPaymentMethod.value.custom_buy_message,
		custom_sell_message: tempPaymentMethod.value.custom_sell_message,
		preference: tempPaymentMethod.value.preference,
	}).then(response => {
        console.log(response.data);
    }).catch(error => {
        console.log(error);
    }).finally(() => {
        props.paymentMethod.name = tempPaymentMethod.value.name;
        props.paymentMethod.handle = tempPaymentMethod.value.handle;
        props.paymentMethod.logo_url = tempPaymentMethod.value.logo_url;
        props.paymentMethod.specific_buy_premium = tempPaymentMethod.value.specific_buy_premium;
        props.paymentMethod.specific_sell_premium = tempPaymentMethod.value.specific_sell_premium;
		props.paymentMethod.custom_buy_message = tempPaymentMethod.value.custom_buy_message;
		props.paymentMethod.custom_sell_message = tempPaymentMethod.value.custom_sell_message;
		props.paymentMethod.preference = tempPaymentMethod.value.preference;
		props.paymentMethod.allowed_currencies = tempPaymentMethod.value.allowed_currencies;
        props.paymentMethod.edit = false;
    });


}
</script>

<template>
    <div :key="paymentMethod.id"
         class="flex flex-col gap-y-2  border-2 rounded-xl border-gray-300 dark:border-zinc-700 p-1">
        <div class="justify-between flex flex-row gap-x-2">
            <div class="flex flex-row gap-x-2 flex-shrink-0">
                <img v-if="paymentMethod.logo_url"
                    :src="paymentMethod.logo_url" class="w-10 h-10 my-auto"/>
                <span class="font-bold mr-1 my-auto">{{ paymentMethod.name }}</span>
            </div>
            <div class="grid grid-cols-2 gap-y-2 flex-grow mx-5">
                <p v-if="paymentMethod.specific_buy_premium"
                  class="mr-1"><span class="font-bold">Buy Premium:</span> {{ paymentMethod.specific_buy_premium }}</p>
                <p v-if="paymentMethod.specific_sell_premium"
                   class="mr-1"><span class="font-bold">Sell Premium:</span> {{ paymentMethod.specific_sell_premium }}
                </p>
                <!--<p class="mr-1 my-auto" v-if="paymentMethod.handle">-->
                <!--    <span class="font-bold">Handle:</span> {{ paymentMethod.handle }}</p>-->
                <!--<p class="mr-1 my-auto" v-if="paymentMethod.custom_message">-->
                <!--    <span class="font-bold">Message:</span> {{ paymentMethod.custom_message }}</p>-->

                <div class="col-span-2 flex flex-row gap-x-2" v-if="tempPaymentMethod.allowed_currencies && tempPaymentMethod.allowed_currencies.length > 0">
                    <span class="font-bold">Currencies:</span>
                    <span v-for="currency in tempPaymentMethod.allowed_currencies" :key="currency" class="font-semibold ">
                        {{ currency }}</span>
                </div>

            </div>
            <div class="flex flex-row gap-x-2 ">
                <p v-if="!paymentMethod.handle && !paymentMethod.custom_message"
                   class="mr-1 my-auto">
                    <span class="font-bold"><span class="text-red-500">(You shouldn't use this payment method till it has a handle or message!)</span></span>
                </p>
                <primary-button class="font-bold mr-1 flex-shrink-0 w-max"
                                @click="paymentMethod.edit = !paymentMethod.edit"
                                v-text="paymentMethod.edit ? 'Cancel' : 'Edit'">
                </primary-button>
            </div>
        </div>

        <div v-if="paymentMethod.edit"  class="grid grid-cols-2 gap-y-2 p-2">
            <label for="name">Name</label>
            <TextInput class="w-full text-left" v-model="tempPaymentMethod.name"/>
            <label for="handle">Handle</label>
            <TextInput class="w-full text-left" v-model="tempPaymentMethod.handle"/>
			<label for="preference">Priority (Not required)</label>
			<input type="number" class="w-full text-left border border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm py-2 px-3 text-base focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
				   v-model="tempPaymentMethod.preference"/>
            <!--<label for="specific_buy_premium">Specific Buy Premium (Not required)</label>-->
            <!--<TextInput class="w-full text-left" v-model="tempPaymentMethod.specific_buy_premium"/>-->
            <!--<label for="specific_sell_premium">Specific Sell Premium (Not required)</label>-->
            <!--<TextInput class="w-full text-left" v-model="tempPaymentMethod.specific_sell_premium"/>-->
			<label for="custom_message">Custom Buy Message (Not required; takes precedence over handle)</label>
			<textarea class="w-full h-20 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm py-2 px-3 text-base focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" v-model="tempPaymentMethod.custom_buy_message"></textarea>
			<label for="custom_message">Custom Sell Message (Not required; takes precedence over handle)</label>
			<textarea class="w-full h-20 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm py-2 px-3 text-base focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" v-model="tempPaymentMethod.custom_sell_message"></textarea>
            <label for="logo_url">Logo URL</label>
            <TextInput class="w-full text-left" v-model="tempPaymentMethod.logo_url"/>
            <!--<label for="allowed_currencies">Currencies (Not required)</label>-->
            <!--<CurrenciesInput :payment_methods="tempPaymentMethod.allowed_currencies"-->
            <!--                 @update:model-value="tempPaymentMethod.allowed_currencies = $event"-->
            <!--                 :currencies="currencies"-->
            <!--                 :key="'edit-currencies' + paymentMethod.id"/>-->
            <div class="col-span-2 flex ">
                <primary-button class=" ml-auto w-max mt-2 text-center" @click="editPaymentMethod">
                    <span class="mx-auto">Save Changes</span>
                </primary-button>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
