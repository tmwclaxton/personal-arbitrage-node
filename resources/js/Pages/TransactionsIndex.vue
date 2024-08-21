<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, Link} from '@inertiajs/vue3';
import GuestLayout from "@/Layouts/GuestLayout.vue";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Pagination from "@/Components/Pagination.vue";
import Table from "@/Components/Table.vue";

const props = defineProps({
    transactions: Object,
});
</script>

<template>
    <Head title="Transactions" />

    <GuestLayout>

        <div class="py-12">
            <div class="w mx-auto sm:px-6 lg:px-8">
                <div class=" overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="flex flex-col items-center">
                        <Table class="" endpoint="/transactions" :rows="transactions.data" />
                    </div>
                    <div class="flex flex-col items-center mt-6">
                        <Pagination :links="transactions.links"/>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
