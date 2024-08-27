<template>

    <!--'col-span-2': showSidebar && offer.accepted,-->
    <!--'col-span-3': !showSidebar && offer.accepted,-->
    <!--'col-span-1': !offer.accepted,-->

    <div class="max-w-md mx-auto bg-white dark:bg-zinc-800 dark:text-zinc-200 dark:border-zinc-700 dark:shadow-lg
    rounded-xl shadow-md overflow-hidden md:max-w-2xl"  :class="{'col-span-2': showSidebar && (offer.accepted || (offer.robots && offer.robots.length > 0)), 'col-span-3': !showSidebar && (offer.accepted || (offer.robots && offer.robots.length > 0)), 'col-span-1': !offer.accepted}">

        <div v-if="offer.status">
            <p class=" text-zinc-500 dark:text-zinc-200 font-bold break-words p-4"
               :class="{'bg-blue-200 dark:bg-blue-800': offer.my_offer, 'bg-red-200 dark:bg-red-700': !offer.my_offer}">
                Status: {{offer.status_message}} · ({{offer.status}})
                <!--<span v-if="offer.my_offer" class="text-blue-500 dark:text-blue-300">-->
                <!--    · Maker Offer-->
                <!--</span>-->
                <span v-if="offer.posted_offer_template_id" class="text-blue-500 dark:text-blue-300">
                    · Template {{ offer.posted_offer_template_id }}
                </span>
            </p>
        </div>
        <div v-else class="mt-4">   </div>
        <!--0, 6, 7, 9, 10-->
        <div class="p-4 pt-0">
         <!--   <div v-if="offer.transaction && (!offer.my_offer &&-->
         <!--(offer.status === 0 || offer.status === 6 || offer.status === 7 || offer.status === 9 || offer.status === 10))"-->
         <!--        class="border border-gray-200 dark:border-zinc-700 "></div>-->
            <div class="grid grid-cols-3  gap-1 p-1">

                <danger-button v-on:click="autoRun" v-if="!offer.accepted && !offer.my_offer"
                               :disabled="offer.job_locked || offer.accepted"
                               class="w-full text-center  h-10 break-words disabled:opacity-50">
                    <p class="text-center w-full">Auto Run</p>
                </danger-button>

                <primary-button v-on:click="uniqueRobot"
                                v-if="!offer.robots || offer.robots.length === 0"
                                class="w-full text-center  h-10 break-words ">
                    <p class="text-center w-full">Create Robots</p>
                </primary-button>

                <primary-button v-on:click="acceptOffer"
                                v-if="!offer.accepted && !offer.my_offer"
                                class="w-full text-center  h-10 break-words ">
                    <p class="text-center w-full">Accept</p>
                </primary-button>

                <primary-button class="w-full text-center  h-10 break-words "
                                v-on:click="payBond"
                                v-if="!offer.my_offer && offer.status === 3 && offer.accepted || offer.my_offer && offer.status === 0">
                    <p class="text-center w-full">Bond</p>
                </primary-button>

                <primary-button v-on:click="payEscrow"
                                v-if="offer.accepted && (offer.status === 6 || offer.status === 7)"
                                class="w-full text-center  h-10 break-words ">
                    <p class="text-center w-full">Escrow</p>
                </primary-button>


                <primary-button class="w-full text-center p-0  h-10 break-words"
                                v-if="offer.accepted && (offer.status === 9 || offer.status === 10)"
                                v-on:click="sendPaymentHandle">
                    <p class="text-center w-full">Auto Chat</p>
                </primary-button>

                <primary-button class="w-full text-center p-0  h-10 break-words"
                                v-if="offer.accepted && (offer.status === 9 || offer.status === 10)"
                                v-on:click="confirmPayment">
                    <p class="text-center w-full">Confirm</p>
                </primary-button>
                <secondary-button class="w-full text-center p-0  h-10 break-words"
                                  v-if="offer.accepted && (offer.status === 9 || offer.status === 10)"
                                  v-on:click="">
                    <p class="text-center w-full">View Chat</p>
                </secondary-button>


                <danger-button v-on:click="collaborativeCancel"
                               v-if="offer.accepted && (offer.status === 9 || offer.status === 10)"
                               class="w-full text-center  h-10 break-words ">
                    <p class="text-center w-full">Collaborative Cancel</p>
                </danger-button>
            </div>

            <div class="border-b border-gray-200 dark:border-zinc-700 mb-2 "></div>
            <div class=" flex flew-row gap-4">


                <div class="flex flex-col max-w-44 flex-shrink-0">
                    <div class="mt-0.5 uppercase tracking-wide text-sm text-indigo-500 font-semibold">
                        <span v-text="offer.provider"></span>
                        <!--<span class="text-zinc-500 dark:text-zinc-200 mx-1">·</span>-->
                        <span class="mt-2 text-zinc-500 dark:text-zinc-200 font-bold">{{
                                offer.accepted && offer.taker ? ' · Accepted' : ''
                            }}</span>

                    </div>
                    <p class="block mt-1  leading-tight font-bold underline dark:text-zinc-200">
                        Offer #{{ offer.robosatsId }}
                    </p>
                    <!--<p class="mt-2 text-zinc-500 dark:text-zinc-200 font-bold">Currency: {{ offer.currency }}</p>-->
                    <p class="text-zinc-500 dark:text-zinc-200 font-bold">Price: {{ offer.price }} {{
                            offer.currency
                        }}</p>
                    <p class="text-zinc-500 dark:text-zinc-200 font-bold">Type: {{ offer.type }} BTC</p>
                    <div v-if="(!offer.accepted && !offer.has_range)" class="flex flex-col">
                        <p class="mt-2 text-zinc-500 dark:text-zinc-200">Amount: {{ offer.amount ?? 'N/A' }}</p>
                        <p class="text-zinc-500 dark:text-zinc-200 text-xs">Sats: {{ offer.satoshis_now ?? 'N/A' }}</p>
                        <p class="text-zinc-500 dark:text-zinc-200 text-xs">Sats Profit:
                            {{ offer.satoshi_amount_profit ?? 'N/A' }}</p>
                    </div>
                    <!-- if accepted offer amount is select hide below!!!-->
                    <div v-if="(!offer.accepted && offer.has_range)" class="flex flex-col">
                        <p class="mt-2 text-zinc-500 dark:text-zinc-200">Min Amount: {{ offer.min_amount ?? 'N/A' }}</p>
                        <p class="text-zinc-500 dark:text-zinc-200 text-xs">Sats: {{
                                offer.min_satoshi_amount ?? 'N/A'
                            }}</p>
                        <p class="text-zinc-500 dark:text-zinc-200 text-xs">Profit:
                            {{ offer.min_satoshi_amount_profit ?? 'N/A' }}</p>
                        <p class="mt-2 text-zinc-500 dark:text-zinc-200">Max Amount: {{ offer.max_amount ?? 'N/A' }}</p>
                        <p class="text-zinc-500 dark:text-zinc-200 text-xs">Sats: {{
                                offer.max_satoshi_amount ?? 'N/A'
                            }}</p>
                        <p class="text-zinc-500 dark:text-zinc-200 text-xs">Profit:
                            {{ offer.max_satoshi_amount_profit ?? 'N/A' }}</p>
                    </div>
                    <div v-if="offer.accepted">
                        <!--    accepted_offer_amount, accepted_offer_amount_sat, accepted_offer_profit_sat-->
                        <p class="mt-2 text-zinc-500 dark:text-zinc-200">Accepted Amount:
                            {{ offer.accepted_offer_amount ?? 'N/A' }}</p>
                        <p class="text-zinc-500 dark:text-zinc-200 text-xs">Sats:
                            {{ offer.accepted_offer_amount_sat ?? 'N/A' }}</p>
                        <p class="text-zinc-500 dark:text-zinc-200 text-xs">Profit:
                            {{ offer.accepted_offer_profit_sat ?? 'N/A' }}</p>
                    </div>


                </div>
                <div class="flex flex-col"><p class="text-zinc-500 dark:text-zinc-200 italic">Expires at:
                    {{ offer.expires_at }}</p>
                    <div class="border border-gray-200 dark:border-zinc-700 my-1"></div>
                    <p class="text-zinc-500 dark:text-zinc-200 italic">Last updated at: {{
                            offer.updated_at_readable
                        }}</p>
                    <div v-if="offer.auto_accept_at" class="border border-gray-200 dark:border-zinc-700 my-1"></div>
                    <p v-if="offer.auto_accept_at" class="text-zinc-500 dark:text-zinc-200 italic font-bold">Auto
                        accepting at: {{ offer.auto_accept_at }}</p>
                    <div v-if="offer.auto_confirm_at" class="border border-gray-200 dark:border-zinc-700 my-1"></div>
                    <p v-if="offer.auto_confirm_at" class="text-zinc-500 dark:text-zinc-200 italic font-bold">Auto
                        confirming at: {{ offer.auto_confirm_at }}</p>
                    <div class="border border-gray-200 dark:border-zinc-700 my-1"></div>
                    <!--<p class="mt-2 text-zinc-500 dark:text-zinc-200">Explicit: {{ offer.is_explicit ? 'Yes' : 'No' }}</p>-->
                    <!--<p class="mt-2 text-zinc-500 dark:text-zinc-200">Satoshis: {{ offer.satoshis ?? 'N/A' }}</p>-->
                    <!--<p class="mt-2 text-zinc-500 dark:text-zinc-200">Maker: {{ offer.maker }}</p>-->
                    <p class=" text-zinc-500 dark:text-zinc-200">Escrow Duration: {{ offer.escrow_duration }}</p>
                    <p class="text-zinc-500 dark:text-zinc-200">Bond Size: {{ offer.bond_size }}</p>
                    <p class="text-zinc-500 dark:text-zinc-200">Premium: {{ offer.premium }}</p>
                    <p class="text-zinc-500 dark:text-zinc-200 font-medium ">Payment Methods: <br><span
                        class="break-words font-bold">{{ offer.payment_methods }}</span></p>

                </div>


                <div v-if="offer.robots && offer.robots.length > 0" class="border-r border-gray-200  "></div>

                <div class="flex flex-col gap-2">
                    <div v-if="offer.robots && offer.robots.length > 0">
                        <p class="  text-zinc-500 dark:text-zinc-200 "><span class="font-bold">Nickname</span>: <br>{{
                                offer.robots[0].nickname
                            }}</p>
                        <p class="mt-2 text-zinc-500 dark:text-zinc-200 "><span class="font-bold">Token</span>:
                            <br>{{ offer.robots[0].token }}</p>
                        <div v-for="robot in offer.robots" :key="robot.id">
                            <p class="mt-2 text-zinc-500 dark:text-zinc-200">Provider: {{ robot.provider }}</p>
                        </div>
                    </div>

                </div>
            </div>


        </div>




    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { defineProps } from 'vue';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps(['offer', 'showSidebar']);

const autoRun = () => {
    console.log('auto run');

    axios.post(route('auto-accept'), {
        offer_id: props.offer.id
    }).then(response => {
        console.log(response);
    }).catch(error => {
        console.log(error);
    });
}

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

const sendPaymentHandle = () => {
    console.log('sending payment handle');

    axios.post(route('send-payment-handle'), {
        offer_id: props.offer.id
    }).then(response => {
        console.log(response);
    }).catch(error => {
        console.log(error);
    });
}

const collaborativeCancel = () => {
    console.log('collaborative cancel');

    axios.post(route('collaborative-cancel'), {
        offer_id: props.offer.id
    }).then(response => {
        console.log(response);
    }).catch(error => {
        console.log(error);
    });
}
</script>
