<script setup>

import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import {ref} from "vue";

const props = defineProps({
    paymentMethod: Object,
});

const tempPaymentMethod = ref({
    name: props.paymentMethod.name,
    handle: props.paymentMethod.handle,
    logo_url: props.paymentMethod.logo_url,
    specific_buy_premium: props.paymentMethod.specific_buy_premium,
    specific_sell_premium: props.paymentMethod.specific_sell_premium,
});



const editPaymentMethod = () => {
    axios.post(route('update-payment-method', {id: props.paymentMethod.id}), {
        name: tempPaymentMethod.value.name,
        handle: tempPaymentMethod.value.handle,
        logo_url: tempPaymentMethod.value.logo_url,
        specific_buy_premium: tempPaymentMethod.value.specific_buy_premium,
        specific_sell_premium: tempPaymentMethod.value.specific_sell_premium,
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
        props.paymentMethod.edit = false;
    });


}
</script>

<template>
    <div :key="paymentMethod.id"
         class="flex flex-col gap-y-2">
        <div v-if="!paymentMethod.edit" class="justify-between flex flex-row gap-x-2">
            <div class="flex flex-row gap-x-2">
                <img :src="paymentMethod.logo_url" class="w-10 h-10"/>
                <span class="font-bold mr-1 my-auto">{{ paymentMethod.name }}</span></div>
            <p v-if="paymentMethod.specific_buy_premium"
               class="mr-1"><span class="font-bold">Buy Premium:</span> {{ paymentMethod.specific_buy_premium }}</p>
            <p v-if="paymentMethod.specific_sell_premium"
               class="mr-1"><span class="font-bold">Sell Premium:</span> {{ paymentMethod.specific_sell_premium }}</p>
            <div class="flex flex-row gap-x-2 ">
                <p class="mr-1 my-auto" v-if="paymentMethod.handle">
                    <span class="font-bold">Handle:</span> {{ paymentMethod.handle }}</p>
                <p v-else class="mr-1 my-auto">
                    <span class="font-bold">Handle:</span> Not Set. <br><span class="text-red-500">(Payment method won't be used!)</span>
                </p>
                <primary-button class="font-bold mr-1 flex-shrink-0 w-max"
                                @click="paymentMethod.edit = !paymentMethod.edit"
                                v-text="paymentMethod.edit ? 'Cancel' : 'Edit'">
                </primary-button>
            </div>
        </div>

        <div v-else class="flex flex-col gap-y-2">
            <label for="name">Name</label>
            <TextInput class="w-full text-left" v-model="tempPaymentMethod.name"/>
            <label for="handle">Handle</label>
            <TextInput class="w-full text-left" v-model="tempPaymentMethod.handle"/>
            <label for="specific_buy_premium">Specific Buy Premium (Not required)</label>
            <TextInput class="w-full text-left" v-model="tempPaymentMethod.specific_buy_premium"/>
            <label for="specific_sell_premium">Specific Sell Premium (Not required)</label>
            <TextInput class="w-full text-left" v-model="tempPaymentMethod.specific_sell_premium"/>
            <label for="logo_url">Logo URL</label>
            <TextInput class="w-full text-left" v-model="tempPaymentMethod.logo_url"/>
            <primary-button class="mt-2" @click="editPaymentMethod">Save Changes</primary-button>
        </div>
    </div>
</template>

<style scoped>

</style>
