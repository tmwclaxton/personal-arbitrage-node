<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, Link} from '@inertiajs/vue3';
import GuestLayout from "@/Layouts/GuestLayout.vue";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Pagination from "@/Components/Pagination.vue";
import Table from "@/Components/Table.vue";


</script>

<template>
    <Head title="Guide" />

    <GuestLayout>

        <div class="py-12">
            <div class=" mx-auto sm:px-6 lg:px-8">
                <p class="text-2xl text-center text-gray-500 dark:text-gray-400">Kit Guide</p>
                <div class="text-sm text-center text-gray-500 dark:text-gray-400">
                    Get started with your kit by following the guide below. (Coming soon - in the meantime, please reach out to us at <a href="https://www.lightningarbitragesolutions.com" class="text-blue-500">Lightning Arbitrage Solutions</a> for assistance)
                </div>

            </div>
        </div>
    </GuestLayout>
</template>
