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
    offers: Object,
});
</script>

<template>
    <Head title="Offers" />

    <GuestLayout>

        <div class="py-12">
            <div class="w mx-auto sm:px-6 lg:px-8">
                <div v-if="offers.data && offers.data.length === 0" class="my-24 border-purple-500 overflow-hidden border-2 shadow-sm sm:rounded-lg w-52 mx-auto">
					<div class="flex flex-col items-center my-auto p-2">
						<div class="text-center  text-2xl  text-gray-500 dark:text-gray-400">
							No offers found
						</div>
					</div>
				</div>
				<div v-else
				  class=" overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="flex flex-col items-center">
                        <Table class="" endpoint="/offer" :rows="offers.data" />
                    </div>
                    <div class="flex flex-col items-center mt-6">
                        <Pagination :links="offers.links"/>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
