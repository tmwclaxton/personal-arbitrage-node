<script setup>
import {Head, Link, router} from '@inertiajs/vue3';
import {defineProps, ref} from 'vue';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {useOfferActionStore} from "@/Stores/OfferActionStore.js";
const offerStore = useOfferActionStore();
const props = defineProps(['offer', 'showSidebar']);
const collapse = ref(true);
</script>


<template>


    <div class="w-full dark:text-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 border border-gray-200 rounded-lg mb-4 overflow-hidden
     dark:shadow-lg shadow-md relative"
		 :class="{'bg-blue-200': offer.type === 'buy', 'bg-red-200': offer.type === 'sell'}">

		<div v-if="offer.status">
		    <p class="bg-zinc-700 text-white dark:text-zinc-200 font-bold break-words p-2.5"
		       :class="{
					'dark:bg-purple-900/50': offer.my_offer,
				   'dark:bg-red-700': !offer.my_offer
				   }">
		        Status: {{offer.status_message}} · ({{offer.status}})
		        <span v-if="offer.posted_offer_template_slug" class="text-blue-500 dark:text-blue-300">
		            · Template {{ offer.posted_offer_template_slug }}
		        </span>
		        <!--    asked_for_cancel-->
		        <span v-if="offer.pending_cancel" class="text-red-500 dark:text-red-300">
		            · Counterparty asked for cancel!
		        </span>
		        <span v-if="offer.asked_for_cancel" class="text-red-500 dark:text-red-300">
		            · We asked for cancel!
		        </span>
		    </p>
		</div>
		<div class="p-2 w-full flex flex-col">
            <p class="text-xs text-zinc-500 dark:text-zinc-200 font-bold mx-auto opacity-70">Offer actions may take a few seconds to complete</p>
			    <div class="grid grid-cols-3  gap-1 p-1">

			        <danger-button v-on:click="offerStore.autoRun(offer)"
								   v-if="!offer.accepted && !offer.my_offer"
			                       :disabled="offer.job_locked || offer.accepted || (offer.robots && offer.robots.length > 0)"
			                       class="w-full text-center  h-10 break-words disabled:opacity-50">
			            <p class="text-center w-full">Robots & Accept</p>
			        </danger-button>

			        <primary-button v-on:click="offerStore.uniqueRobot(offer.id)"
			                        v-if="!offer.robots || offer.robots.length === 0"
			                        class="w-full text-center  h-10 break-words ">
			            <p class="text-center w-full">Create Robots</p>
			        </primary-button>

			        <primary-button v-on:click="offerStore.acceptOffer(offer)"
			                        v-if="((!offer.accepted && !offer.my_offer) || (offer.status === 1 && !offer.my_offer)) && (offer.robots && offer.robots.length > 0)"
			                        class="w-full text-center  h-10 break-words ">
			            <p class="text-center w-full">Accept</p>
			        </primary-button>

					<primary-button class="w-full text-center  h-10 break-words "
									v-on:click="offerStore.payBond(offer.id)"
									v-if="(!offer.my_offer && offer.status === 3 && offer.accepted || offer.my_offer && offer.status === 0)">
						<p class="text-center w-full">Bond</p>
					</primary-button>
					<primary-button class="w-full text-center  h-10 break-words "
									v-on:click="offerStore.pause(offer.id)"
									v-if="(offer.my_offer && offer.status < 3 && offer.status !== 2)">
						<p class="text-center w-full">Pause</p>
					</primary-button>
					<primary-button class="w-full text-center  h-10 break-words "
									v-on:click="offerStore.unpause(offer.id)"
									v-if="(offer.my_offer && offer.status === 2)">
						<p class="text-center w-full">Unpause</p>
					</primary-button>
					<danger-button class="w-full text-center  h-10 break-words "
									v-on:click="offerStore.cancel(offer.id)"
									v-if="(!offer.my_offer && offer.status >= 3 && offer.accepted || offer.my_offer && offer.status >= 0) && offer.status <= 7">
						<p class="text-center w-full">Cancel</p>
					</danger-button>
					<primary-button v-on:click="offerStore.payEscrow(offer.id)"
									v-if="offer.type === 'sell' && (offer.accepted && (offer.status === 6 || offer.status === 7))"
									class="w-full text-center  h-10 break-words ">
						<p class="text-center w-full">Escrow</p>
					</primary-button>
					<primary-button v-on:click="offerStore.generateInvoice(offer.id)"
									v-if="offer.type === 'buy' && (offer.accepted && (offer.status === 6 || offer.status === 8))"
									class="w-full text-center  h-10 break-words ">
						<p class="text-center w-full">Invoice</p>
					</primary-button>


			        <primary-button class="w-full text-center p-0  h-10 break-words"
			                        v-if="offer.accepted && (offer.status === 9 || offer.status === 10)"
			                        v-on:click="offerStore.sendPaymentHandle(offer.id)">
			            <p class="text-center w-full">Auto Chat</p>
			        </primary-button>

			        <primary-button class="w-full text-center p-0  h-10 break-words"
			                        v-if="offer.accepted && (offer.status === 9 || offer.status === 10)"
			                        v-on:click="offerStore.confirmPayment(offer.id)">
			            <p class="text-center w-full">Confirm</p>
			        </primary-button>


			        <Link :href="route('offers.show', {offer_id: offer.id})">
						<secondary-button class="w-full text-center p-0  h-10 break-words"
										  v-if="offer.accepted && (offer.status === 9 || offer.status === 10)"
										  v-on:click="">
							<p class="text-center w-full">View Chat</p>
						</secondary-button>
					</Link>


			        <danger-button v-on:click="offerStore.collaborativeCancel(offer.id)"
			                       v-if="offer.accepted && (offer.status === 9 || offer.status === 10)"
			                       class="w-full text-center  h-10 break-words ">
			            <p class="text-center w-full">Collaborative Cancel</p>
			        </danger-button>



			</div>
			<div class="flex flex-col gap-y-2 mb-1">
				<div class="flex flex-row ">
					<div class="flex flex-col mr-2 w-1/3 min-w-52">
						<div class="flex flex-row gap-x-1">
							<div
							  class="inline-flex rounded-full overflow-hidden w-10 h-10 flex items-center justify-center">
								<img v-if="offer.provider === 'lake'" src="/images/lake.jpg" alt="Lake"
									 class="w-full h-full">
								<img v-if="offer.provider === 'satstralia'" src="/images/satstralia.png"
									 alt="Satstralia"
									 class="w-full h-full">
								<img v-if="offer.provider === 'temple'" src="/images/temple.png" alt="Temple"
									 class="w-full h-full">
								<img v-if="offer.provider === 'veneto'" src="/images/veneto.png" alt="Veneto"
									 class="w-full h-full">
							</div>
							<p class="block my-auto leading-tight font-bold underline dark:text-zinc-200">
								Offer #{{ offer.robosatsId }} ({{ offer.my_offer ? 'Maker' : 'Taker' }})
							</p>
						</div>
						<p v-if="!['lake', 'satstralia', 'temple', 'veneto'].includes(offer.provider)"
						   class="text-zinc-500 dark:text-zinc-200 font-bold">Provider: {{ offer.provider }}</p>

						<!--<p class="mt-2 text-zinc-500 dark:text-zinc-200 font-bold">Currency: {{ offer.currency }}</p>-->
						<p class="text-zinc-500 dark:text-zinc-200 font-bold">Price: {{ offer.price }} {{
								offer.currency
							}}
							<span class="text-zinc-500 dark:text-zinc-200 font-normal mx-1 ">
						{{ offer.premium }}</span>
							<span class="text-zinc-500 dark:text-zinc-200 font-normal">B{{ offer.bond_size }}</span>
							<span class=" text-zinc-500 dark:text-zinc-200 font-normal mx-1">E: {{ offer.escrow_duration }}H</span>

						</p>
						<p class="text-zinc-500 dark:text-zinc-200 italic">Updated {{
								offer.updated_at_readable
							}}</p>
						<p class="text-zinc-500 dark:text-zinc-200 italic">Expires
							{{ offer.expires_at }}</p>
						<div v-if="offer.auto_accept_at" class="border border-gray-200 dark:border-zinc-700 my-1"></div>
						<p v-if="offer.auto_accept_at" class="text-zinc-500 dark:text-zinc-200 italic font-bold">Auto
							accepting at: {{ offer.auto_accept_at }}</p>
						<div v-if="offer.auto_confirm_at" class="border border-gray-200 dark:border-zinc-700 my-1"></div>
						<p v-if="offer.auto_confirm_at" class="text-zinc-500 dark:text-zinc-200 italic font-bold">Auto
							confirming at: {{ offer.auto_confirm_at }}</p>

					</div>
					<div class="flex flex-col gap-y-1">
						<div v-if="offer.robots && offer.robots.length > 0">
							<p class="  text-zinc-500 dark:text-zinc-200 "><span class="font-bold">Nickname</span>: {{
									offer.robots[0].nickname
								}}</p>
							<p class="text-zinc-500 dark:text-zinc-200 break-all"><span class="font-bold">Token</span>:
								{{ offer.robots[0].token }}</p>
						</div>
						<div v-if="(!offer.accepted && !offer.has_range)" class="flex flex-col">
							<p class="text-zinc-500 dark:text-zinc-200">Amount: {{ offer.amount ?? 'N/A' }}</p>
							<p class="text-zinc-500 dark:text-zinc-200 text-xs">Sats: {{
									offer.satoshis_now ?? 'N/A'
								}}</p>
							<p class="text-zinc-500 dark:text-zinc-200 text-xs">Sats Profit:
								{{ offer.satoshi_amount_profit ?? 'N/A' }}</p>
						</div>
						<!-- if accepted offer amount is select hide below!!!-->
						<div v-if="(!offer.accepted && offer.has_range)" class="flex flex-row gap-x-2">
							<div class="flex flex-col">
								<p class="text-zinc-500 dark:text-zinc-200">Min Amount: {{
										offer.min_amount ?? 'N/A'
									}}</p>
								<p class="text-zinc-500 dark:text-zinc-200 text-xs">Sats: {{
										offer.min_satoshi_amount ?? 'N/A'
									}}</p>
								<p class="text-zinc-500 dark:text-zinc-200 text-xs">Profit:
									{{ offer.min_satoshi_amount_profit ?? 'N/A' }}</p>

							</div>
							<div class="flex flex-col">
								<p class="text-zinc-500 dark:text-zinc-200">Max Amount: {{
										offer.max_amount ?? 'N/A'
									}}</p>
								<p class="text-zinc-500 dark:text-zinc-200 text-xs">Sats: {{
										offer.max_satoshi_amount ?? 'N/A'
									}}</p>
								<p class="text-zinc-500 dark:text-zinc-200 text-xs">Profit:
									{{ offer.max_satoshi_amount_profit ?? 'N/A' }}</p>
							</div>

						</div>
						<div v-if="offer.accepted">
							<!--    accepted_offer_amount, accepted_offer_amount_sat, accepted_offer_profit_sat-->
							<p class="text-zinc-500 dark:text-zinc-200">Accepted Amount:
								{{ offer.accepted_offer_amount ?? 'N/A' }}</p>
							<p class="text-zinc-500 dark:text-zinc-200 text-xs">Sats:
								{{ offer.accepted_offer_amount_sat ?? 'N/A' }}</p>
							<p class="text-zinc-500 dark:text-zinc-200 text-xs">Profit:
								{{ offer.accepted_offer_profit_sat ?? 'N/A' }}</p>
						</div>


					</div>
				</div>
				<div class="flex flex-row gap-1 flex-wrap justify-between">
					<div class="flex flex-row gap-1 flex-wrap ">
						<span v-for="method in offer.payment_methods"
							  class="break-words text-zinc-500 dark:text-zinc-200 font-medium rounded-lg bg-zinc-100 dark:bg-zinc-900 p-1 h-max">
						{{ method }}
						</span>
					</div>
					<Link :href="route('offers.show', {offer_id: offer.id})">
						<secondary-button class="w-full text-center p-0  h-10 break-words"
										  v-on:click="">
							<p class="text-center w-full">View Offer</p>
						</secondary-button>
					</Link>
				</div>
			</div>


		</div>

        <!--<div v-if="offer.status">-->
        <!--    <p class=" text-zinc-500 dark:text-zinc-200 font-bold break-words p-4"-->
        <!--       :class="{'bg-blue-200 dark:bg-blue-800': offer.my_offer, 'bg-red-200 dark:bg-red-700': !offer.my_offer}">-->
        <!--        Status: {{offer.status_message}} · ({{offer.status}})-->
        <!--        &lt;!&ndash;<span v-if="offer.my_offer" class="text-blue-500 dark:text-blue-300">&ndash;&gt;-->
        <!--        &lt;!&ndash;    · Maker Offer&ndash;&gt;-->
        <!--        &lt;!&ndash;</span>&ndash;&gt;-->
        <!--        <span v-if="offer.posted_offer_template_slug" class="text-blue-500 dark:text-blue-300">-->
        <!--            · Template {{ offer.posted_offer_template_slug }}-->
        <!--        </span>-->
        <!--    &lt;!&ndash;    asked_for_cancel&ndash;&gt;-->
        <!--        <span v-if="offer.pending_cancel" class="text-red-500 dark:text-red-300">-->
        <!--            · Counterparty asked for cancel!-->
        <!--        </span>-->
        <!--        <span v-if="offer.asked_for_cancel" class="text-red-500 dark:text-red-300">-->
        <!--            · We asked for cancel!-->
        <!--        </span>-->
        <!--    </p>-->
        <!--</div>-->
        <!--<div v-else class="mt-4">   </div>-->
        <!--<div class="p-4 pt-0">-->
        <!--    <div class="grid grid-cols-3  gap-1 p-1">-->

        <!--        <danger-button v-on:click="offerStore.autoRun(offer.id)"-->
		<!--					   v-if="!offer.accepted && !offer.my_offer"-->
        <!--                       :disabled="offer.job_locked || offer.accepted"-->
        <!--                       class="w-full text-center  h-10 break-words disabled:opacity-50">-->
        <!--            <p class="text-center w-full">Auto Run</p>-->
        <!--        </danger-button>-->

        <!--        <primary-button v-on:click="offerStore.uniqueRobot(offer.id)"-->
        <!--                        v-if="!offer.robots || offer.robots.length === 0"-->
        <!--                        class="w-full text-center  h-10 break-words ">-->
        <!--            <p class="text-center w-full">Create Robots</p>-->
        <!--        </primary-button>-->

        <!--        <primary-button v-on:click="offerStore.acceptOffer(offer.id)"-->
        <!--                        v-if="(!offer.accepted && !offer.my_offer) || (offer.status === 1 && !offer.my_offer)"-->
        <!--                        class="w-full text-center  h-10 break-words ">-->
        <!--            <p class="text-center w-full">Accept</p>-->
        <!--        </primary-button>-->

        <!--        <primary-button class="w-full text-center  h-10 break-words "-->
        <!--                        v-on:click="offerStore.payBond(offer.id)"-->
        <!--                        v-if="offer.type === 'sell' && (!offer.my_offer && offer.status === 3 && offer.accepted || offer.my_offer && offer.status === 0)">-->
        <!--            <p class="text-center w-full">Bond</p>-->
        <!--        </primary-button>-->
		<!--		-->
		<!--		<primary-button v-on:click="offerStore.payEscrow(offer.id)"-->
		<!--						v-if="offer.type === 'sell' && (offer.accepted && (offer.status === 6 || offer.status === 7))"-->
		<!--						class="w-full text-center  h-10 break-words ">-->
		<!--			<p class="text-center w-full">Escrow</p>-->
		<!--		</primary-button>-->
		<!--		<primary-button v-on:click="offerStore.generateInvoice(offer.id)"-->
		<!--						v-if="offer.type === 'buy' && (offer.accepted && (offer.status === 6 || offer.status === 8))"-->
		<!--						class="w-full text-center  h-10 break-words ">-->
		<!--			<p class="text-center w-full">Invoice</p>-->
		<!--		</primary-button>-->


        <!--        <primary-button class="w-full text-center p-0  h-10 break-words"-->
        <!--                        v-if="offer.accepted && (offer.status === 9 || offer.status === 10)"-->
        <!--                        v-on:click="offerStore.sendPaymentHandle(offer.id)">-->
        <!--            <p class="text-center w-full">Auto Chat</p>-->
        <!--        </primary-button>-->

        <!--        <primary-button class="w-full text-center p-0  h-10 break-words"-->
        <!--                        v-if="offer.accepted && (offer.status === 9 || offer.status === 10)"-->
        <!--                        v-on:click="offerStore.confirmPayment(offer.id)">-->
        <!--            <p class="text-center w-full">Confirm</p>-->
        <!--        </primary-button>-->
        <!--        <Link :href="route('offers.show', {offer_id: offer.id})">-->
		<!--			<secondary-button class="w-full text-center p-0  h-10 break-words"-->
		<!--							  v-if="offer.accepted && (offer.status === 9 || offer.status === 10)"-->
		<!--							  v-on:click="">-->
		<!--				<p class="text-center w-full">View Chat</p>-->
		<!--			</secondary-button>-->
		<!--		</Link>-->


        <!--        <danger-button v-on:click="offerStore.collaborativeCancel(offer.id)"-->
        <!--                       v-if="offer.accepted && (offer.status === 9 || offer.status === 10)"-->
        <!--                       class="w-full text-center  h-10 break-words ">-->
        <!--            <p class="text-center w-full">Collaborative Cancel</p>-->
        <!--        </danger-button>-->
        <!--    </div>-->

        <!--    <div class="border-b border-gray-200 dark:border-zinc-700 mb-2 "></div>-->
        <!--    <div class=" flex flew-row gap-4">-->


        <!--        <div class="flex flex-col max-w-44 flex-shrink-0">-->
        <!--            <div class="mt-0.5 uppercase tracking-wide text-sm text-indigo-500 font-semibold">-->
        <!--                <span v-text="offer.provider"></span>-->
        <!--                &lt;!&ndash;<span class="text-zinc-500 dark:text-zinc-200 mx-1">·</span>&ndash;&gt;-->
        <!--                <span class="mt-2 text-zinc-500 dark:text-zinc-200 font-bold">{{-->
        <!--                        offer.accepted && offer.taker ? ' · Accepted' : ''-->
        <!--                    }}</span>-->

        <!--            </div>-->
        <!--            <p class="block mt-1  leading-tight font-bold underline dark:text-zinc-200">-->
        <!--                Offer #{{ offer.robosatsId }}-->
        <!--            </p>-->
        <!--            &lt;!&ndash;<p class="mt-2 text-zinc-500 dark:text-zinc-200 font-bold">Currency: {{ offer.currency }}</p>&ndash;&gt;-->
        <!--            <p class="text-zinc-500 dark:text-zinc-200 font-bold">Price: {{ offer.price }} {{-->
        <!--                    offer.currency-->
        <!--                }}</p>-->
        <!--            <p class="text-zinc-500 dark:text-zinc-200 font-bold">Type:-->
		<!--				{{ offer.type }} BTC-->
		<!--				&lt;!&ndash;({{ offer.my_offer ? 'Maker' : 'Taker' }})&ndash;&gt;-->
		<!--			</p>-->
		<!--			&lt;!&ndash;<p class="text-zinc-500 dark:text-zinc-200 font-bold">&ndash;&gt;-->
		<!--			&lt;!&ndash;	{{ offer.my_offer ? 'They are' : 'We would be' }}&ndash;&gt;-->
		<!--			&lt;!&ndash;	{{ offer.type }}ing BTC&ndash;&gt;-->
		<!--			&lt;!&ndash;</p>&ndash;&gt;-->
        <!--            <div v-if="(!offer.accepted && !offer.has_range)" class="flex flex-col">-->
        <!--                <p class="mt-2 text-zinc-500 dark:text-zinc-200">Amount: {{ offer.amount ?? 'N/A' }}</p>-->
        <!--                <p class="text-zinc-500 dark:text-zinc-200 text-xs">Sats: {{ offer.satoshis_now ?? 'N/A' }}</p>-->
        <!--                <p class="text-zinc-500 dark:text-zinc-200 text-xs">Sats Profit:-->
        <!--                    {{ offer.satoshi_amount_profit ?? 'N/A' }}</p>-->
        <!--            </div>-->
        <!--            &lt;!&ndash; if accepted offer amount is select hide below!!!&ndash;&gt;-->
        <!--            <div v-if="(!offer.accepted && offer.has_range)" class="flex flex-col">-->
        <!--                <p class="mt-2 text-zinc-500 dark:text-zinc-200">Min Amount: {{ offer.min_amount ?? 'N/A' }}</p>-->
        <!--                <p class="text-zinc-500 dark:text-zinc-200 text-xs">Sats: {{-->
        <!--                        offer.min_satoshi_amount ?? 'N/A'-->
        <!--                    }}</p>-->
        <!--                <p class="text-zinc-500 dark:text-zinc-200 text-xs">Profit:-->
        <!--                    {{ offer.min_satoshi_amount_profit ?? 'N/A' }}</p>-->
        <!--                <p class="mt-2 text-zinc-500 dark:text-zinc-200">Max Amount: {{ offer.max_amount ?? 'N/A' }}</p>-->
        <!--                <p class="text-zinc-500 dark:text-zinc-200 text-xs">Sats: {{-->
        <!--                        offer.max_satoshi_amount ?? 'N/A'-->
        <!--                    }}</p>-->
        <!--                <p class="text-zinc-500 dark:text-zinc-200 text-xs">Profit:-->
        <!--                    {{ offer.max_satoshi_amount_profit ?? 'N/A' }}</p>-->
        <!--            </div>-->
        <!--            <div v-if="offer.accepted">-->
        <!--                &lt;!&ndash;    accepted_offer_amount, accepted_offer_amount_sat, accepted_offer_profit_sat&ndash;&gt;-->
        <!--                <p class="mt-2 text-zinc-500 dark:text-zinc-200">Accepted Amount:-->
        <!--                    {{ offer.accepted_offer_amount ?? 'N/A' }}</p>-->
        <!--                <p class="text-zinc-500 dark:text-zinc-200 text-xs">Sats:-->
        <!--                    {{ offer.accepted_offer_amount_sat ?? 'N/A' }}</p>-->
        <!--                <p class="text-zinc-500 dark:text-zinc-200 text-xs">Profit:-->
        <!--                    {{ offer.accepted_offer_profit_sat ?? 'N/A' }}</p>-->
        <!--            </div>-->


        <!--        </div>-->
        <!--        <div class="flex flex-col">-->
		<!--			<p class="text-zinc-500 dark:text-zinc-200 italic">Expires at:-->
        <!--            {{ offer.expires_at }}</p>-->
        <!--            <div class="border border-gray-200 dark:border-zinc-700 my-1"></div>-->
        <!--            <p class="text-zinc-500 dark:text-zinc-200 italic">Last updated at: {{-->
        <!--                    offer.updated_at_readable-->
        <!--                }}</p>-->
        <!--            <div v-if="offer.auto_accept_at" class="border border-gray-200 dark:border-zinc-700 my-1"></div>-->
        <!--            <p v-if="offer.auto_accept_at" class="text-zinc-500 dark:text-zinc-200 italic font-bold">Auto-->
        <!--                accepting at: {{ offer.auto_accept_at }}</p>-->
        <!--            <div v-if="offer.auto_confirm_at" class="border border-gray-200 dark:border-zinc-700 my-1"></div>-->
        <!--            <p v-if="offer.auto_confirm_at" class="text-zinc-500 dark:text-zinc-200 italic font-bold">Auto-->
        <!--                confirming at: {{ offer.auto_confirm_at }}</p>-->
        <!--            <div class="border border-gray-200 dark:border-zinc-700 my-1"></div>-->
        <!--            &lt;!&ndash;<p class="mt-2 text-zinc-500 dark:text-zinc-200">Explicit: {{ offer.is_explicit ? 'Yes' : 'No' }}</p>&ndash;&gt;-->
        <!--            &lt;!&ndash;<p class="mt-2 text-zinc-500 dark:text-zinc-200">Satoshis: {{ offer.satoshis ?? 'N/A' }}</p>&ndash;&gt;-->
        <!--            &lt;!&ndash;<p class="mt-2 text-zinc-500 dark:text-zinc-200">Maker: {{ offer.maker }}</p>&ndash;&gt;-->
        <!--            <p class=" text-zinc-500 dark:text-zinc-200">Escrow Duration: {{ offer.escrow_duration }}</p>-->
        <!--            <p class="text-zinc-500 dark:text-zinc-200">Bond Size: {{ offer.bond_size }}</p>-->
        <!--            <p class="text-zinc-500 dark:text-zinc-200">Premium: {{ offer.premium }}</p>-->
        <!--            <p class="text-zinc-500 dark:text-zinc-200 font-medium ">Payment Methods: <br><span-->
        <!--                class="break-words font-bold">{{ offer.payment_methods }}</span></p>-->



        <!--        </div>-->


        <!--        <div v-if="offer.robots && offer.robots.length > 0" class="border-r border-gray-200  "></div>-->

        <!--        <div class="flex flex-col gap-2">-->
        <!--            <div v-if="offer.robots && offer.robots.length > 0">-->
        <!--                <p class="  text-zinc-500 dark:text-zinc-200 "><span class="font-bold">Nickname</span>: <br>{{-->
        <!--                        offer.robots[0].nickname-->
        <!--                    }}</p>-->
        <!--                <p class="mt-2 text-zinc-500 dark:text-zinc-200 break-all"><span class="font-bold">Token</span>:-->
        <!--                    <br>{{ offer.robots[0].token }}</p>-->
        <!--                &lt;!&ndash;<div v-for="robot in offer.robots" :key="robot.id">&ndash;&gt;-->
        <!--                &lt;!&ndash;    <p class="mt-2 text-zinc-500 dark:text-zinc-200">Provider: {{ robot.provider }}</p>&ndash;&gt;-->
        <!--                &lt;!&ndash;</div>&ndash;&gt;-->
		<!--				<p v-if="offer.status === 9 || offer.status === 10"-->
		<!--				   class="text-zinc-500 dark:text-zinc-200 font-medium mt-2">Expected Reference ID: <br><span-->
		<!--				  class="break-words font-bold">{{ offer.id }}</span></p>-->
		<!--			-->
		<!--			</div>-->

        <!--        </div>-->
        <!--    </div>-->


        <!--</div>-->




    </div>
</template>

