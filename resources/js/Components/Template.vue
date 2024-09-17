<script setup>


import {ref} from "vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import ProvidersInput from "@/Components/ProvidersInput.vue";
import PaymentsInput from "@/Components/PaymentsInput.vue";
import ToggleButton from "@/Components/ToggleButton.vue";
import {router} from "@inertiajs/vue3";
import DangerButton from "@/Components/DangerButton.vue";
import {useConfirmModalStore} from "@/Stores/ConfirmModelStore.js";
import toastStore from "@/Stores/ToastStore.js";

const props = defineProps({
    template: Object,
    payment_methods: {
        type: Array,
        default: ['Revolut', 'Paypal Friends & Family', 'Strike', 'Wise', 'Faster Payments', 'Instant SEPA']
    },
    providers: {
        type: Array,
        default: ['satstralia', 'temple', 'lake', 'veneto']
    },
	currencies: {
		type: Array,
		default: ['USD', 'EUR', 'GBP', 'BTC', 'ETH']
	}
});

const emits = defineEmits(['refresh']);

const offerEditTemplate = ref({
    type: props.template.type,
    min: props.template.min_amount,
    max: props.template.max_amount,
	latitude: props.template.latitude,
	longitude: props.template.longitude,
    premium: props.template.premium,
    currency: props.template.currency,
    paymentMethods: props.template.payment_methods,
    provider: props.template.provider,
    bondSize: props.template.bond_size,
    autoCreate: props.template.auto_create,
    providers: [],
    quantity: props.template.quantity,
    cooldown: props.template.cooldown,
    ttl: props.template.ttl,
	escrow_time: props.template.escrow_time,
});

const update = (refreshPage = false) => {
    axios.post(route('edit-template', {id: props.template.id}), {
        type: offerEditTemplate.value.type,
        min_amount: parseInt(offerEditTemplate.value.min),
        max_amount: parseInt(offerEditTemplate.value.max),
		latitude: offerEditTemplate.value.latitude,
		longitude: offerEditTemplate.value.longitude,
        premium: offerEditTemplate.value.premium,
        currency: offerEditTemplate.value.currency,
        payment_methods: offerEditTemplate.value.paymentMethods,
        provider: offerEditTemplate.value.providers,
        bond_size: parseInt(offerEditTemplate.value.bondSize),
        auto_create: offerEditTemplate.value.autoCreate,
        quantity: offerEditTemplate.value.quantity,
        cooldown: offerEditTemplate.value.cooldown,
        ttl: offerEditTemplate.value.ttl,
		escrow_time: offerEditTemplate.value.escrow_time,
    }).then(response => {
        console.log(response.data);
		if (refreshPage) {
			// reload the page
			emits('refresh');
			toastStore.add({
				message: 'Template updated',
				type: "success",
			});
		}
    }).catch(error => {
        console.log(error);
    });

}

const deleteTemplate = () => {
	useConfirmModalStore().buttonOneText = 'Cancel';
	useConfirmModalStore().buttonTwoText = 'Delete';
	useConfirmModalStore().title = 'Are you sure, this will delete your template?';
	useConfirmModalStore().show = true;
	useConfirmModalStore().continue = () => {
		axios.get(route('delete-template', {id: props.template.id})).then(response => {
			console.log(response.data);
			// reload the page
			emits('refresh');
			toastStore.add({
				message: 'Template deleted',
				type: "error",
			});
		}).catch(error => {
			console.log(error);
		});
	};
}



// convert the payment methods from json to array
offerEditTemplate.value.paymentMethods = JSON.parse(offerEditTemplate.value.paymentMethods);

// convert the provider from json to array
offerEditTemplate.value.providers = JSON.parse(offerEditTemplate.value.provider);

</script>

<template>

	
	<tr  :key="template.id" class="bg-white dark:bg-zinc-900">
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<div class="text-sm font-bold text-gray-900 dark:text-gray-200">{{ template.slug }}</div>
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<select v-model="offerEditTemplate.type"
					class="w-max pr-8 block mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm py-2 px-3 text-base focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
				<option value="buy">Buy</option>
				<option value="sell">Sell</option>
			</select>
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<text-input  v-model="offerEditTemplate.min" label="Min" class="w-20"/>
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<text-input v-model="offerEditTemplate.max" label="Max" class="w-20"/>
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<text-input v-model="offerEditTemplate.premium" label="Premium" class="w-20"/>
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<text-input v-model="offerEditTemplate.latitude" label="Latitude" class="w-24"/>
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<text-input v-model="offerEditTemplate.longitude" label="Longitude" class="w-24"/>
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<text-input v-model="offerEditTemplate.bondSize" label="Bond Size" class="w-10"/>
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<!--<text-input v-model="offerEditTemplate.currency" label="Currency" class="w-16"/>-->
			<select v-model="offerEditTemplate.currency"
					class="w-max pr-6 block  bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm py-2 px-3 text-base focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
				<option v-for="currency in currencies" :value="currency" v-text="currency"></option>
			</select>
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center ">
			<payments-input :key="template.id + 'payment'" :payment_methods="offerEditTemplate.paymentMethods"
				v-model="offerEditTemplate.paymentMethods" label="Payment Methods" class="w-96" :options="payment_methods"/>
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center w-20">
			<providers-input :key="template.id + 'provider'"
				:providers="offerEditTemplate.providers"
				:options="providers"  v-model="offerEditTemplate.providers" label="Provider" class="w-full "/>

		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<text-input v-model="offerEditTemplate.cooldown" label="Cooldown" class="w-14"/>
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<text-input v-model="offerEditTemplate.ttl" label="TTL" class="w-16"/>
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<text-input v-model="offerEditTemplate.escrow_time" label="Escrow Time" class="w-16"/>
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<text-input v-model="offerEditTemplate.quantity" label="Quantity" class="w-12"/>
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<toggle-button v-model="offerEditTemplate.autoCreate" label="Auto Create" />
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<div class="flex flex-col gap-y-2 ">
				<primary-button @click="update(true)" v-text="'Update'"/>
				<primary-button :id="'update' + template.id" @click="update(false)" v-text="'Update'" class="hidden"/>
				<danger-button @click="deleteTemplate" v-text="'Delete'"/>
			</div>
		</td>
	</tr>
</template>

<style scoped>

</style>
