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

const props = defineProps({
    postedOffers: Object,
});


const create = () => {
    axios.post(route('create-template'), {
        min: offerTemplate.value.min,
        max: offerTemplate.value.max,
        premium: offerTemplate.value.premium,
        currency: offerTemplate.value.currency,
        paymentMethods: offerTemplate.value.paymentMethods,
        bondSize: offerTemplate.value.bondSize,
        autoCreate: offerTemplate.value.autoCreate
    }).then(response => {
        console.log(response.data);
    }).catch(error => {
        console.log(error);
    });
}


const autoCreate = () => {
    axios.post(route('offer.autocreate'), {

    }).then(response => {
        console.log(response.data);
    }).catch(error => {
        console.log(error);
    });

}

const offerTemplate = ref({
    min: 0,
    max: 0,
    premium: 0,
    currency: '',
    paymentMethods: '',
    bondSize: 0,
    autoCreate: false
});


</script>


<template>
    <Head title="Config" />

    <guest-layout>
        <div class=" min-h-screen flex flex-row w-screen">
            <div class="border-r border-gray-200 w-1/4">
                <div class="flex flex-col items-center">
                        <h1 class="text-2xl font-bold underline mb-1">Create an offer template</h1>
                        <label class="text-sm text-gray-500">Min amount</label>
                        <text-input v-model="offerTemplate.min" label="Min" />
                        <label class="text-sm text-gray-500">Max amount (optional i.e. put 0)</label>
                        <text-input v-model="offerTemplate.max" label="Max" />
                        <label class="text-sm text-gray-500">Premium</label>
                        <text-input v-model="offerTemplate.premium" label="Premium" />
                        <label class="text-sm text-gray-500">Bond Size</label>
                        <text-input v-model="offerTemplate.bondSize" label="Bond Size" />
                        <label class="text-sm text-gray-500">Currency (GBP, USD, EUR)</label>
                        <text-input v-model="offerTemplate.currency" label="Currency" />
                        <payments-input class="mx-16" v-model="offerTemplate.paymentMethods" label="Payment Methods" />
                        <label class="text-sm text-gray-500 mt-5">Auto Create</label>
                        <toggle-button v-model="offerTemplate.autoCreate" label="Auto Create" />

                        <primary-button class="mt-4" @click="create">Create</primary-button>

                    <div class="border-b border-gray-200 w-full my-5"></div>
                    <primary-button @click="autoCreate">Auto Create</primary-button>

                </div>
            </div>



        </div>
    </guest-layout>
</template>
