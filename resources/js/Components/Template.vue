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
});

const offerEditTemplate = ref({
    min: props.template.min_amount,
    max: props.template.max_amount,
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
        min_amount: parseInt(offerEditTemplate.value.min),
        max_amount: parseInt(offerEditTemplate.value.max),
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
        location.reload();
    }).catch(error => {
        console.log(error);
    });

}

const deleteTemplate = () => {
    axios.get(route('delete-template', {id: props.template.id})).then(response => {
        console.log(response.data);
        // reload the page
        location.reload();
    }).catch(error => {
        console.log(error);
    });
}

const editMode = ref(false);

// convert the payment methods from json to array
offerEditTemplate.value.paymentMethods = JSON.parse(offerEditTemplate.value.paymentMethods);

//  convert provider from String to Array
offerEditTemplate.value.providers = offerEditTemplate.value.provider.split(' ');

</script>

<template>
    <div :key="template.id"
         class="rounded-lg border border-gray-200 w-full my-2 p-2 bg-white dark:bg-zinc-900">
        <p class="font-bold" v-text="'Template ID: ' + template.id"></p>
        <div class="border-t border-gray-200 my-2"/>
        <div v-if="!editMode" class="grid grid-cols-4  p-2">
            <p>
                <span class="font-bold">Type: </span>
                {{template.type }}
            </p>
            <p>
                <span class="font-bold" v-text="template.max_amount && template.max_amount > 0 ? 'Min: ' : 'Amount: '"></span>
                {{template.min_amount }}
            </p>
            <p v-if="template.max_amount && template.max_amount > 0">
                <span class="font-bold">Max: </span>
                {{template.max_amount }}
            </p>

            <p>
                <span class="font-bold">Quantity: </span>
                {{template.quantity }}
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
                <span class="font-bold">Cooldown: </span>
                {{template.cooldown }}
            </p>

            <p>
                <span class="font-bold">TTL: </span>
                {{template.ttl }}
            </p>

            <p>
                <span class="font-bold">Auto Create: </span>
                <span v-text="template.auto_create ? 'True' : 'False'"></span>
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

        <div v-else class="grid grid-cols-5 gap-2 p-2">
            <div class="flex flex-row gap-x-2">
                <p class="font-bold my-auto">Min:</p>
                <text-input v-model="offerEditTemplate.min" label="Min" class="w-full"/>
            </div>
            <div class="flex flex-row gap-x-2">
                <p class="font-bold my-auto">Max:</p>
                <text-input v-model="offerEditTemplate.max" label="Max" class="w-full"/>
            </div>
            <div class="flex flex-row gap-x-2">
                <p class="font-bold my-auto">Quantity:</p>
                <text-input v-model="offerEditTemplate.quantity" label="Quantity" class="w-full"/>
            </div>
            <div class="flex flex-row gap-x-2">
                <p class="font-bold my-auto">Premium:</p>
                <text-input v-model="offerEditTemplate.premium" label="Premium" class="w-full"/>
            </div>
            <div class="flex flex-row gap-x-2">
                <p class="font-bold my-auto">Bond Size:</p>
                <text-input v-model="offerEditTemplate.bondSize" label="Bond Size" class="w-full"/>
            </div>
            <div class="flex flex-row gap-x-2">
                <p class="font-bold my-auto">Currency:</p>
                <text-input v-model="offerEditTemplate.currency" label="Currency" class="w-full"/>
            </div>
            <div class="flex flex-row gap-x-2">
                <p class="font-bold my-auto">Cooldown:</p>
                <text-input v-model="offerEditTemplate.cooldown" label="Cooldown" class="w-full"/>
            </div>
            <div class="flex flex-row gap-x-2">
                <p class="font-bold my-auto">TTL:</p>
                <text-input v-model="offerEditTemplate.ttl" label="TTL" class="w-full"/>
            </div>
            <div class="flex flex-row gap-x-2 col-span-2 mt-2">
                <p class="font-bold my-auto ">Auto Create:</p>
                <toggle-button v-model="offerEditTemplate.autoCreate" label="Auto Create" class=""/>
            </div>
            <div class="flex flex-row gap-x-2 col-span-5">
                <p class="font-bold my-auto">Payment Methods:</p>
                <payments-input :key="template.id + 'payment'"
                    v-model="offerEditTemplate.paymentMethods" label="Payment Methods" class="w-full"/>
            </div>
            <div class="flex flex-row gap-x-2 col-span-5">
                <p class="font-bold my-auto">Provider:</p>
                <providers-input :key="template.id + 'provider'"
                    v-model="offerEditTemplate.providers" label="Provider" class="w-full ml-10"/>
            </div>

            <!--<text-input v-model="offerEditTemplate.max" label="Max" />-->
            <!--<text-input v-model="offerEditTemplate.premium" label="Premium" />-->
            <!--<text-input v-model="offerEditTemplate.bondSize" label="Bond Size" />-->
            <!--<text-input v-model="offerEditTemplate.currency" label="Currency" />-->
            <!--<payments-input  v-model="offerEditTemplate.paymentMethods" label="Payment Methods" />-->
            <!--<providers-input v-model="offerEditTemplate.provider" label="Provider" />-->
            <!--<toggle-button v-model="offerEditTemplate.autoCreate" label="Auto Create" />-->
        </div>

        <div class="flex flex-row justify-between">
            <div class="flex flex-row gap-x-2">
                <secondary-button @click="editMode = !editMode" v-text="editMode ? 'Cancel' : 'Edit'"/>
                <primary-button v-if="editMode" @click="update" v-text="'Update'"/>
            </div>
            <danger-button v-if="editMode" @click="deleteTemplate" v-text="'Delete'"/>
        </div>
    </div>
</template>

<style scoped>

</style>
