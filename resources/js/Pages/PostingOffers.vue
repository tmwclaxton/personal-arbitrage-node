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
import L from 'leaflet';

const props = defineProps({
    templates: Object,
    paymentMethods: Array,
	providers: Array,
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
    }).then(response => {
        console.log(response.data);
		refreshPage();

    }).catch(error => {
        console.log(error);
    });
}


const autoCreate = () => {
    axios.post(route('offer.autocreate'), {

    }).then(response => {
        console.log(response.data);
		refreshPage();
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
});

const refreshPage = () => {
	router.visit(route('offers.posting.index'))
}


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
                        <text-input v-model="offerTemplate.currency" label="Currency" />
                        <label class="text-sm text-gray-500">Quantity</label>
                        <text-input v-model="offerTemplate.quantity" label="Quantity" />
                        <payments-input class="mx-16 col-span-4" v-model="offerTemplate.paymentMethods" label="Payment Methods" :options="paymentMethods" />
                        <providers-input class="mx-16 col-span-4" :options="providers" v-model="offerTemplate.provider" label="Provider" />
                        <label class="text-sm text-gray-500">Cooldown</label>
                        <text-input v-model="offerTemplate.cooldown" label="Cooldown" />
                        <label class="text-sm text-gray-500">TTL</label>
                        <text-input v-model="offerTemplate.ttl" label="TTL" />
                        <label class="text-sm text-gray-500 mt-5">Active</label>
                        <toggle-button v-model="offerTemplate.autoCreate" label="Auto Create" />

                        <primary-button class="mt-4" @click="create">Create</primary-button>
					

                </div>
            </div>

			<div class="flex flex-col items-center w-full overflow-x-overflow mx-10">
				<h1 class="text-2xl font-bold underline mb-1">Templates</h1>
				<div class="  flex flex-col items-center">

					
					<table class=" divide-y divide-gray-200">
						<thead class="bg-gray-50 mx-10">
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
									Active
								</th>
								<th scope="col" class="px-1 py-3  border-r text-center text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:bg-gray-800 dark:border-gray-700">
									Actions
								</th>
								
							</tr>
						</thead>
						<tbody class="bg-white divide-y divide-gray-200">
							<!--<template v-for="template in templates" :key="template.id" :template="template"-->
							<!--		  :providers="providers" :paymentMethods="paymentMethods">-->
							
							<!--</template>-->
							<Template v-for="template in templates" :template="template" :options="paymentMethods" :key="template.id"
									  @refresh="refreshPage" />
						</tbody>
					</table>
					
					

				</div>
			</div>


        </div>
    </guest-layout>
</template>
