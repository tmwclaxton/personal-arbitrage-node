<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, Link} from '@inertiajs/vue3';
import GuestLayout from "@/Layouts/GuestLayout.vue";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
} from 'chart.js';
import {Line} from 'vue-chartjs';


ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
);

const props = defineProps({
    dates: Object,
    volumes: Object
});

// const data = {
//     labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
//     datasets: [
//         {
//             label: 'Data One',
//             backgroundColor: '#f87979',
//             data: [40, 39, 10, 40, 39, 80, 40]
//         }
//     ]
// }

const data = {
    labels: props.dates,
    datasets: [
        {
            label: 'Daily Volume',
            backgroundColor: '#f87979',
            data: props.volumes
        }
    ]
}
const options = {
    responsive: true,
    maintainAspectRatio: false
}

</script>

<template>
    <Head title="Graphs"/>

    <GuestLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <Line :data="data" :options="options"/>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
