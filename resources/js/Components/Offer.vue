<template>
    <div class="max-w-md p-8 mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-2xl">
            <div class=" flex flew-row gap-4">
                <div class="flex flex-col w-44 flex-shrink-0">
                    <div class="uppercase tracking-wide text-sm text-indigo-500 font-semibold">{{
                            offer.provider
                        }}
                    </div>
                    <p class="block mt-1 text-lg leading-tight font-medium text-black  ">
                        Offer #{{ offer.robosatsId }}
                    </p>
                    <!--<p class="mt-2 text-gray-500 font-bold">Currency: {{ offer.currency }}</p>-->
                    <p class="mt-2 text-gray-500 font-bold">Price: {{ offer.price }} {{ offer.currency }}</p>
                    <p class="mt-2 text-gray-500 font-bold">Type: {{ offer.type }} BTC</p>
                    <p v-if="!offer.has_range"  class="mt-2 text-gray-500">Amount: {{ offer.amount ?? 'N/A' }}</p>
                    <p v-if="!offer.has_range"  class="mt-0.5 text-gray-500 text-xs">Sats: {{ offer.satoshis_now ?? 'N/A' }}</p>
                    <p v-if="!offer.has_range"  class="mt-0.5 text-gray-500 text-xs">Sats Profit: {{ offer.satoshi_amount_profit ?? 'N/A' }}</p>
                    <p v-if="offer.has_range" class="mt-2 text-gray-500">Min Amount: {{ offer.min_amount ?? 'N/A' }}</p>
                    <p v-if="offer.has_range" class="mt-0.5 text-gray-500 text-xs">Sats: {{ offer.min_satoshi_amount ?? 'N/A' }}</p>
                    <p v-if="offer.has_range" class="mt-0.5 text-gray-500 text-xs">Profit: {{ offer.min_satoshi_amount_profit ?? 'N/A' }}</p>
                    <p v-if="offer.has_range" class="mt-2 text-gray-500">Max Amount: {{ offer.max_amount ?? 'N/A' }}</p>
                    <p v-if="offer.has_range" class="mt-0.5 text-gray-500 text-xs">Sats: {{ offer.max_satoshi_amount ?? 'N/A' }}</p>
                    <p v-if="offer.has_range" class="mt-0.5 text-gray-500 text-xs">Profit: {{ offer.max_satoshi_amount_profit ?? 'N/A' }}</p>
                    <p class="mt-2 text-gray-500">Premium: {{ offer.premium }}</p>
                    <p class="mt-2 text-gray-500 ">Payment Methods: <br><span class="break-words font-bold">{{ offer.payment_methods }}</span></p>
                    <p class="mt-2 text-gray-500 font-bold">Accepted: {{ offer.accepted ? 'Yes' : 'No' }}</p>
                </div>
                <div class="flex flex-col"><p class="mt-2 text-gray-500 italic">Expires at: {{ offer.expires_at }}</p>
                    <!--<p class="mt-2 text-gray-500">Explicit: {{ offer.is_explicit ? 'Yes' : 'No' }}</p>-->
                    <!--<p class="mt-2 text-gray-500">Satoshis: {{ offer.satoshis ?? 'N/A' }}</p>-->
                    <p class="mt-2 text-gray-500">Maker: {{ offer.maker }}</p>
                    <p class="mt-2 text-gray-500">Escrow Duration: {{ offer.escrow_duration }}</p>
                    <p class="mt-2 text-gray-500">Bond Size: {{ offer.bond_size }}</p>
                </div>
            </div>

            <div class="border border-gray-200 my-4"></div>
            <div class="grid grid-cols-2 gap-3">

                <primary-button v-on:click="uniqueRobot"
                                class="w-full text-center">
                    Create Unique Robot
                </primary-button>

                <primary-button v-on:click="acceptOffer"
                                class="w-full text-center">
                    Accept Offer
                </primary-button>

                <primary-button class="w-full text-center" v-on:click="payBond">
                    Pay Bond
                </primary-button>

                <primary-button v-on:click="payEscrow"
                    class="w-full text-center">
                    Pay Escrow
                </primary-button>


                <primary-button class="w-full text-center">
                    Send Payment Handle
                </primary-button>

                <primary-button class="w-full text-center" v-on:click="confirmPayment">
                    Confirm Payment Received
                </primary-button>
            </div>

            <div v-if="offer.transaction" class="border border-gray-200 my-4"></div>

            <div v-if="offer.transaction">
                <!--<p class="mt-2 text-gray-500 font-bold">Transaction ID: {{-->
                <!--    offer.transaction.id-->
                <!--}}</p>-->
                <p class="mt-2 text-gray-500 font-bold">Transaction Status: {{
                        offer.transaction.status
                    }}</p>
            </div>

            <div v-if="offer.robots" class="border border-gray-200 my-4"></div>

            <div v-if="offer.robots && offer.robots.length > 0">
                <p class="mt-2 text-gray-500 "><span class="font-bold">Nickname</span>: <br>{{ offer.robots[0].nickname }}</p>
                <p class="mt-2 text-gray-500 "><span class="font-bold">Token</span>: <br>{{ offer.robots[0].token }}</p>
                <div v-for="robot in offer.robots" :key="robot.id">
                    <p class="mt-2 text-gray-500">Provider: {{ robot.provider }}</p>
                </div>
            </div>



    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { defineProps } from 'vue';
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps(['offer']);

const uniqueRobot = () => {
    console.log('creating unique robot');

    axios.post(route('create-robot'), {
        offer_id: props.offer.id
    }).then(response => {
        console.log(response);
    }).catch(error => {
        console.log(error);
    });
}

// when accept offer click send post request to /accept-offer with offer_id
const acceptOffer = () => {
    console.log('accepting offer');

    axios.post(route('accept-offer'), {
        offer_id: props.offer.id
    }).then(response => {
        console.log(response);
    }).catch(error => {
        console.log(error);
    });
}

const payEscrow = () => {
    console.log('paying escrow');

    axios.post(route('pay-escrow'), {
        offer_id: props.offer.id
    }).then(response => {
        console.log(response);
    }).catch(error => {
        console.log(error);
    });
}

const payBond = () => {
    console.log('paying bond');

    axios.post(route('pay-bond'), {
        offer_id: props.offer.id
    }).then(response => {
        console.log(response);
    }).catch(error => {
        console.log(error);
    });
}

const confirmPayment = () => {
    console.log('confirming payment');

    axios.post(route('confirm-payment'), {
        offer_id: props.offer.id
    }).then(response => {
        console.log(response);
    }).catch(error => {
        console.log(error);
    });
}
</script>
