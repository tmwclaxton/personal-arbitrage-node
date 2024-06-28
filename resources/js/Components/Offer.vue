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
                    <p v-if="offer.has_range" class="mt-2 text-gray-500">Min Amount: {{ offer.min_amount ?? 'N/A' }}</p>
                    <p v-if="offer.has_range" class="mt-2 text-gray-500">Max Amount: {{ offer.max_amount ?? 'N/A' }}</p>
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
                    <p class="mt-2 text-gray-500">Satoshis Now: {{ offer.satoshis_now }}</p>
                </div>
            </div>

            <div class="border border-gray-200 my-4"></div>
            <div class="grid grid-cols-2 gap-3">
                <primary-button class="w-full text-center">
                    Accept Offer
                </primary-button>

                <primary-button class="w-full text-center">
                    Pay Bond
                </primary-button>

                <primary-button class="w-full text-center">
                    Pay Escrow
                </primary-button>


                <primary-button class="w-full text-center">
                    Send Revolut Tag
                </primary-button>

                <primary-button class="w-full text-center">
                    Confirm Payment Received
                </primary-button>
            </div>



    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { defineProps } from 'vue';
import PrimaryButton from "@/Components/PrimaryButton.vue";

defineProps({
    offer: Object,
});
</script>
