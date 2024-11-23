<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ConfirmModal from "@/Modals/ConfirmModal.vue";
import ToastList from "@/Components/Toast/ToastList.vue";
</script>

<template>
    <div class="min-h-screen flex flex-col   pt-6 sm:pt-0 ">
		<ConfirmModal />
		<ToastList/>
        <div>
			<a href="https://www.lightningarbitragesolutions.com"
			   class="font-bold text-2xl mx-auto text-center pt-5 ">
				<!--Lightning Arbitrage Solutions-->
				<!--<img src="/images/logo.png" alt="Lightning Arbitrage Solutions" class="w-32 mx-auto"/>-->
				<img src="/images/logoLight.png" alt="Lightning Arbitrage Solutions" class="w-72 mx-auto dark:hidden"/>
				<img src="/images/logoDark.png" alt="Lightning Arbitrage Solutions" class="w-72 mx-auto hidden dark:block"/>

			</a>
            <div class="w-full flex flex-row flex-wrap gap-3 mt-2 mx-auto justify-center">
                <Link :href="route('welcome')" >
                    <primary-button class="h-12">
                        Live Offers
                    </primary-button>
                </Link>

                <Link :href="route('dashboard.index')">
                    <primary-button  class="h-12">
                        Config
                    </primary-button>
                </Link>

                <Link :href="route('offers.completed')" >
                    <primary-button class="h-12">
                        Offer Management
                    </primary-button>
                </Link>


				<Link :href="route('offers.posting.index')" >
					<primary-button class="h-12">
						Offer Templates
					</primary-button>
				</Link>

				<Link :href="route('transactions.index')" >
                    <primary-button class="h-12">
                        Transactions
                    </primary-button>
                </Link>
                <Link :href="route('payments.index')" class="opacity-50 pointer-events-none">
                    <primary-button class="h-12">
                        Payments
                    </primary-button>
                </Link>
                <Link :href="route('purchases.index')" >
                    <primary-button class="h-12">
                        Bitcoin Purchases
                    </primary-button>
                </Link>
                <Link :href="route('graphs.index')" >
                    <primary-button class="h-12">
                        Graphs
                    </primary-button>
                </Link>
                <Link :href="route('tax-returns.index')" >
                    <primary-button class="h-12">
                        Tax Reports
                    </primary-button>
                </Link>
                <Link :href="route('logs.index')" >
                    <primary-button class="h-12">
                        Logs
                    </primary-button>
                </Link>
                <Link :href="route('guide.index')" >
                    <primary-button class="h-12">
                        Guide
                    </primary-button>
                </Link>
            </div>
            <div class="my-5 border-b-2 border-gray-300 dark:border-zinc-700"></div>
        </div>

        <div
            class=""
        >
            <slot />
        </div>
    </div>
</template>
