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
import Template from "@/Components/Template.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import toastStore from "@/Stores/ToastStore.js";


const props = defineProps({
    templates: Object,
    paymentMethods: Array,
	providers: Array,
	currencies: Array,
});


const create = () => {
    axios.post(route('create-template'), {
        type: offerTemplate.value.type,
        min_amount	: parseInt(offerTemplate.value.min),
        max_amount: parseInt(offerTemplate.value.max),
		latitude: offerTemplate.value.latitude,
		longitude: offerTemplate.value.longitude,
        premium: offerTemplate.value.premium,
        currency: offerTemplate.value.currency,
        payment_methods: offerTemplate.value.paymentMethods,
        provider: offerTemplate.value.provider,
        bond_size: parseInt(offerTemplate.value.bondSize),
        auto_create: offerTemplate.value.autoCreate,
        quantity: offerTemplate.value.quantity,
        cooldown: offerTemplate.value.cooldown,
        ttl: offerTemplate.value.ttl,
		escrow_time: offerTemplate.value.escrow_time,
    }).then(response => {
        console.log(response.data);
		refreshPage();
		
		toastStore.add({
			message: 'Template created',
			type: "success",
		});

    }).catch(error => {
        console.log(error);
    });
}



const offerTemplate = ref({
    type: 'sell',
    min: 30,
    max: 100,
	latitude: 0,
	longitude: 0,
    premium: 20,
    currency: 'GBP',
    paymentMethods: ['Revolut'],
    provider: ['satstralia'],
    bondSize: 3,
    autoCreate: true,
    quantity: 1,
    cooldown: 600,
    ttl: 3600,
	escrow_time: 3600,
});

const refreshPage = () => {
	router.visit(route('offers.posting.index'), {preserveScroll: true, preserveState: true});
}

const updateAll = () => {
	
	// foreach click button using id :id="'update' + template.id"
	for (let template of props.templates) {
	 	document.getElementById('update' + template.id).click();
 	}
	
	refreshPage();
	
	toastStore.add({
		message: 'All templates updated',
		type: "success",
	});
};

const hideSidebar = ref(false);
</script>


<template>
    <Head title="Config" />

    <guest-layout>
		<div class="w-1/6 flex flex-row">
			<primary-button @click="hideSidebar = !hideSidebar" class="w-max mx-auto">Toggle Sidebar</primary-button>
		</div>
        <div class=" min-h-screen flex flex-row w-screen">
            <div v-if="!hideSidebar"
			  class="border-r border-gray-200 w-1/4">
				<h1 class="text-2xl font-bold underline mb-1 text-center">Create an offer template</h1>
                <div class="flex flex-col flex-wrap items-center mx-auto gap-2 max-w-xl ">
                        <label class="text-sm text-gray-500">Offer Type</label>
                        <select v-model="offerTemplate.type" class="w-36 block  bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm py-2 px-3 text-base focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="buy">Buy</option>
                            <option value="sell">Sell</option>
                        </select>
                        <label class="text-sm text-gray-500">Min amount</label>
                        <text-input v-model="offerTemplate.min" label="Min" />
                        <label class="text-sm text-gray-500">Max amount (optional i.e. put 0)</label>
                        <text-input v-model="offerTemplate.max" label="Max" />
						<label class="text-sm text-gray-500">Latitude (optional i.e. put 0)</label>
						<text-input v-model="offerTemplate.latitude" label="Latitude" />
						<label class="text-sm text-gray-500">Longitude (optional i.e. put 0)</label>
						<text-input v-model="offerTemplate.longitude" label="Longitude" />
                        <label class="text-sm text-gray-500">Premium</label>
                        <text-input v-model="offerTemplate.premium" label="Premium" />
                        <label class="text-sm text-gray-500">Bond Size</label>
                        <text-input v-model="offerTemplate.bondSize" label="Bond Size" />
                        <label class="text-sm text-gray-500">Currency (GBP, USD, EUR)</label>
                        <select v-model="offerTemplate.currency"
						class="w-max pr-6 block  bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm py-2 px-3 text-base focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
							<option v-for="currency in currencies" :value="currency" v-text="currency"></option>
						</select>
                        <label class="text-sm text-gray-500">Quantity</label>
                        <text-input v-model="offerTemplate.quantity" label="Quantity" />
						<label class="text-sm text-gray-500">Payment Methods</label>
                        <payments-input class="mx-16 col-span-4" v-model="offerTemplate.paymentMethods" label="Payment Methods" :options="paymentMethods" />
						<label class="text-sm text-gray-500">Providers</label>
                        <providers-input class="mx-16 col-span-4" :options="providers" v-model="offerTemplate.provider" label="Provider" />
                        <label class="text-sm text-gray-500">Cooldown</label>
                        <text-input v-model="offerTemplate.cooldown" label="Cooldown" />
                        <label class="text-sm text-gray-500">TTL</label>
                        <text-input v-model="offerTemplate.ttl" label="TTL" />
						<label class="text-sm text-gray-500">Escrow Time</label>
						<text-input v-model="offerTemplate.escrow_time" label="Escrow Time" />
                        <label class="text-sm text-gray-500 mt-5">Active</label>
                        <toggle-button v-model="offerTemplate.autoCreate" label="Auto Create" />

                        <primary-button class="mt-4" @click="create">Create</primary-button>
					

                </div>
            </div>

			<div class="flex flex-col items-center w-3/4 overflow-x-overflow px-5" :class="{'w-full': hideSidebar}">
				<div class="flex flex-row gap-x-2 mb-3">
					<h1 class="text-2xl font-bold underline mb-1">Templates</h1>
					<secondary-button @click="updateAll">Update All</secondary-button>
				</div>
				<div class="w-full overflow-x-scroll mx-5  flex flex-col rounded border">
					
					
					<table class="divide-y divide-gray-200">
						<thead class="bg-gray-50 px-10">
							<tr>
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									Template ID
								</th>
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									Type
								</th>
								
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									Min
								</th>
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									Max
								</th>
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									Premium
								</th>
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									Latitude
								</th>
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									Longitude
								</th>
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									Bond Size
								</th>
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									Currency
								</th>
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									Payment Methods
								</th>
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									Provider
								</th>
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									Cooldown
								</th>
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									TTL
								</th>
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									Escrow Time
								</th>
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									Qty
								</th>
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									Active
								</th>
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									Actions
								</th>
								
							</tr>
						</thead>
						<tbody class="bg-white divide-y divide-gray-200">
							<Template
							  v-for="template in templates"
							  :template="template"
							  :payment_methods="paymentMethods"
							  :key="template.id"
							  :currencies="currencies"
							  @refresh="refreshPage"
							/>
						</tbody>
					</table>
					
					

				</div>
			</div>


        </div>
    </guest-layout>
</template>
