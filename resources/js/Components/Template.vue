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

const props = defineProps({
    template: Object,
    payment_methods: {
        type: Array,
        default: ['Revolut', 'Paypal Friends & Family', 'Strike', 'Wise', 'Faster Payments', 'Instant SEPA']
    },
    providers: {
        type: Array,
        default: ['satstralia', 'temple', 'lake', 'veneto']
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
});

const update = () => {


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
    }).then(response => {
        console.log(response.data);
		// reload the page
		emits('refresh');
    }).catch(error => {
        console.log(error);
    });

}

const deleteTemplate = () => {
    axios.get(route('delete-template', {id: props.template.id})).then(response => {
        console.log(response.data);
		// reload the page
		emits('refresh');
		
    }).catch(error => {
        console.log(error);
    });
}

const editMode = ref(true);

// convert the payment methods from json to array
offerEditTemplate.value.paymentMethods = JSON.parse(offerEditTemplate.value.paymentMethods);

//  convert provider from String to Array
offerEditTemplate.value.providers = offerEditTemplate.value.provider.split(' ');

</script>

<template>
    <!--<div :key="template.id"-->
    <!--     class="rounded-lg border border-gray-200 w-full my-2 p-2 bg-white dark:bg-zinc-900">-->
    <!--    <p class="font-bold" v-text="'Template ID: ' + template.id"></p>-->
    <!--    <div class="border-t border-gray-200 my-2"/>-->
	
    <!--    <div class="grid grid-cols-5 gap-2 p-2">-->
	<!--		<div class="flex flex-row gap-x-2">-->
	<!--			<label class="font-bold my-auto">Type:</label>-->
	<!--			<select v-model="offerEditTemplate.type"-->
	<!--					class="w-36 block mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm py-2 px-3 text-base focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">-->
	<!--				<option value="buy">Buy</option>-->
	<!--				<option value="sell">Sell</option>-->
	<!--			</select>-->
	<!--		</div>-->
    <!--        <div class="flex flex-row gap-x-2">-->
    <!--            <p class="font-bold my-auto">Min:</p>-->
    <!--            <text-input v-model="offerEditTemplate.min" label="Min" class="w-full"/>-->
    <!--        </div>-->
    <!--        <div class="flex flex-row gap-x-2">-->
    <!--            <p class="font-bold my-auto">Max:</p>-->
    <!--            <text-input v-model="offerEditTemplate.max" label="Max" class="w-full"/>-->
    <!--        </div>-->
	<!--		<div class="flex flex-row gap-x-2">-->
	<!--			<p class="font-bold my-auto">Latitude:</p>-->
	<!--			<text-input v-model="offerEditTemplate.latitude" label="Latitude" class="w-full"/>-->
	<!--		</div>-->
	<!--		<div class="flex flex-row gap-x-2">-->
	<!--			<p class="font-bold my-auto">Longitude:</p>-->
	<!--			<text-input v-model="offerEditTemplate.longitude" label="Longitude" class="w-full"/>-->
	<!--		</div>-->
    <!--        <div class="flex flex-row gap-x-2">-->
    <!--            <p class="font-bold my-auto">Quantity:</p>-->
    <!--            <text-input v-model="offerEditTemplate.quantity" label="Quantity" class="w-full"/>-->
    <!--        </div>-->
    <!--        <div class="flex flex-row gap-x-2">-->
    <!--            <p class="font-bold my-auto">Premium:</p>-->
    <!--            <text-input v-model="offerEditTemplate.premium" label="Premium" class="w-full"/>-->
    <!--        </div>-->
    <!--        <div class="flex flex-row gap-x-2">-->
    <!--            <p class="font-bold my-auto">Bond Size:</p>-->
    <!--            <text-input v-model="offerEditTemplate.bondSize" label="Bond Size" class="w-full"/>-->
    <!--        </div>-->
    <!--        <div class="flex flex-row gap-x-2">-->
    <!--            <p class="font-bold my-auto">Currency:</p>-->
    <!--            <text-input v-model="offerEditTemplate.currency" label="Currency" class="w-full"/>-->
    <!--        </div>-->
    <!--        <div class="flex flex-row gap-x-2">-->
    <!--            <p class="font-bold my-auto">Cooldown:</p>-->
    <!--            <text-input v-model="offerEditTemplate.cooldown" label="Cooldown" class="w-full"/>-->
    <!--        </div>-->
    <!--        <div class="flex flex-row gap-x-2">-->
    <!--            <p class="font-bold my-auto">TTL:</p>-->
    <!--            <text-input v-model="offerEditTemplate.ttl" label="TTL" class="w-full"/>-->
    <!--        </div>-->
    <!--        <div class="flex flex-row gap-x-2 col-span-2 mt-2">-->
    <!--            <p class="font-bold my-auto ">Auto Create:</p>-->
    <!--            <toggle-button v-model="offerEditTemplate.autoCreate" label="Auto Create" class=""/>-->
    <!--        </div>-->
    <!--        <div class="flex flex-row gap-x-2 col-span-5">-->
    <!--            <p class="font-bold my-auto">Payment Methods:</p>-->
    <!--            <payments-input :key="template.id + 'payment'" :payment_methods="offerEditTemplate.paymentMethods"-->
    <!--                v-model="offerEditTemplate.paymentMethods" label="Payment Methods" class="w-full" :options="payment_methods"/>-->
    <!--        </div>-->
    <!--        <div class="flex flex-row gap-x-2 col-span-5">-->
    <!--            <p class="font-bold my-auto">Provider:</p>-->
    <!--            <providers-input :key="template.id + 'provider'"-->
    <!--                :providers="offerEditTemplate.providers"-->
    <!--                :options="providers"-->
    <!--                v-model="offerEditTemplate.providers" label="Provider" class="w-full ml-10"/>-->
    <!--        </div>-->
	
    <!--        &lt;!&ndash;<text-input v-model="offerEditTemplate.max" label="Max" />&ndash;&gt;-->
    <!--        &lt;!&ndash;<text-input v-model="offerEditTemplate.premium" label="Premium" />&ndash;&gt;-->
    <!--        &lt;!&ndash;<text-input v-model="offerEditTemplate.bondSize" label="Bond Size" />&ndash;&gt;-->
    <!--        &lt;!&ndash;<text-input v-model="offerEditTemplate.currency" label="Currency" />&ndash;&gt;-->
    <!--        &lt;!&ndash;<payments-input  v-model="offerEditTemplate.paymentMethods" label="Payment Methods" />&ndash;&gt;-->
    <!--        &lt;!&ndash;<providers-input v-model="offerEditTemplate.provider" label="Provider" />&ndash;&gt;-->
    <!--        &lt;!&ndash;<toggle-button v-model="offerEditTemplate.autoCreate" label="Auto Create" />&ndash;&gt;-->
    <!--    </div>-->
	
    <!--    <div class="flex flex-row justify-between">-->
    <!--        <div class="flex flex-row gap-x-2">-->
    <!--            <secondary-button @click="editMode = !editMode" v-text="editMode ? 'Cancel' : 'Edit'"/>-->
    <!--            <primary-button v-if="editMode" @click="update" v-text="'Update'"/>-->
    <!--        </div>-->
    <!--        <danger-button v-if="editMode" @click="deleteTemplate" v-text="'Delete'"/>-->
    <!--    </div>-->
    <!--</div>-->
	
	<tr  :key="template.id" class="bg-white dark:bg-zinc-900">
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<div class="text-sm text-gray-900 dark:text-gray-200">{{ template.slug }}</div>
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
			<text-input v-model="offerEditTemplate.currency" label="Currency" class="w-16"/>
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center w-20">
			<payments-input  v-model="offerEditTemplate.paymentMethods" label="Payment Methods" />
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center w-20">
			<providers-input v-model="offerEditTemplate.provider" label="Provider" />
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<text-input v-model="offerEditTemplate.cooldown" label="Cooldown" class="w-14"/>
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<text-input v-model="offerEditTemplate.ttl" label="TTL" class="w-16"/>
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<toggle-button v-model="offerEditTemplate.autoCreate" label="Auto Create" />
		</td>
		<td class="px-1 py-4 whitespace-nowrap text-center">
			<div class="flex flex-col gap-y-2 ">
				<primary-button  @click="update" v-text="'Update'"/>
				<danger-button @click="deleteTemplate" v-text="'Delete'"/>
			</div>
		</td>
	</tr>
</template>

<style scoped>

</style>
