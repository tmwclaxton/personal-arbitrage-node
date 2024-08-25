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
import ProvidersInput from "@/Components/ProvidersInput.vue";

const props = defineProps({
    templates: Object,
});


const create = () => {
    axios.post(route('create-template'), {
        min_amount	: parseInt(offerTemplate.value.min),
        max_amount: parseInt(offerTemplate.value.max),
        premium: parseInt(offerTemplate.value.premium),
        currency: offerTemplate.value.currency,
        payment_methods: offerTemplate.value.paymentMethods,
        provider: offerTemplate.value.provider,
        bond_size: parseInt(offerTemplate.value.bondSize),
        auto_create: offerTemplate.value.autoCreate,
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
    min: 20,
    max: 0,
    premium: 99,
    currency: 'GBP',
    paymentMethods: ['Revolut'],
    provider: ['satstralia'],
    bondSize: 3,
    autoCreate: true
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
                        <providers-input class="mx-16" v-model="offerTemplate.provider" label="Provider" />
                        <label class="text-sm text-gray-500 mt-5">Auto Create</label>
                        <toggle-button v-model="offerTemplate.autoCreate" label="Auto Create" />

                        <primary-button class="mt-4" @click="create">Create</primary-button>

                    <div class="border-b border-gray-200 w-full my-5"></div>
                    <primary-button @click="autoCreate">Auto Create</primary-button>

                </div>
            </div>

            <div class="w-3/4">
                <div class="flex flex-col items-center">
                    <h1 class="text-2xl font-bold underline mb-1">Posted Offers</h1>
                    <div class="w-3/4 flex flex-col items-center">
                        <div v-for="template in templates" :key="template.id"
                             class="rounded-lg border border-gray-200 w-full my-2 p-2 bg-white dark:bg-zinc-900">
                            <p class="font-bold" v-text="'Template ID: ' + template.id"></p>
                            <div class="grid grid-cols-4 border-t border-gray-200 p-2">
                                <p>
                                    <span class="font-bold" v-text="template.max_amount && template.max_amount > 0 ? 'Min: ' + template.min_amount + ' - ' : 'Amount: '"></span>
                                    {{template.min_amount }}
                                </p>
                                <p v-if="template.max_amount && template.max_amount > 0">
                                    <span class="font-bold">Max: </span>
                                    {{template.max_amount }}
                                </p>

                                <p>
                                    <span class="font-bold">Premium: </span>
                                    {{template.premium }}
                                </p>

                                <p>
                                    <span class="font-bold">Bond Size: </span>
                                    {{template.bond_size }}
                                </p>

                                <p>
                                    <span class="font-bold">Currency: </span>
                                    {{template.currency }}
                                </p>

                                <p>
                                    <span class="font-bold">Auto Create: </span>
                                    {{template.auto_create }}
                                </p>

                                <p class="col-span-4 my-1">
                                    <span class="font-bold">Payment Methods: </span>
                                    {{template.payment_methods }}
                                </p>

                                <p class="col-span-4">
                                    <span class="font-bold">Provider: </span>
                                    {{template.provider }}
                                </p>


                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </guest-layout>
</template>
