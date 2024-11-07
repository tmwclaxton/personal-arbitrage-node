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
    <Head title="Tax Return Management" />

    <GuestLayout>

        <div class="py-12">
            <div class=" mx-auto sm:px-6 lg:px-8">
                <p class="text-2xl text-center text-gray-500 dark:text-gray-400">Tax Return Management</p>
                <div class="text-sm text-center text-gray-500 dark:text-gray-400">
                    No matter what currency you use or country you live in, we will have you covered with our tax return management service. (Coming soon)
                </div>

            </div>
        </div>
    </GuestLayout>
</template>
