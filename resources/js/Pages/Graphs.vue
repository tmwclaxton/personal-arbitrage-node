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
    BarElement,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
} from 'chart.js';
import { Bar, Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
);

const props = defineProps({
    dates: Object,
    volumesByCurrency: Object,
    profits: Object,
    profitsInGBP: Object,
});

// Define color scheme for the datasets
const colors = ['#f87979', '#79f879', '#7979f8', '#f8b879', '#b8f879']; // Extend this list as needed

// Prepare the chart data
const data = {
    labels: props.dates,
    datasets: Object.keys(props.volumesByCurrency).map((currency, index) => ({
        label: currency,
        backgroundColor: colors[index % colors.length],
        data: props.volumesByCurrency[currency],
    }))
};

const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
        },
        title: {
            display: true,
            text: 'Daily Volume by Currency'
        },
    },
    scales: {
        x: {
            stacked: true,
        },
        y: {
            stacked: true,
        },
    },
};

const dataLine = {
    labels: props.dates,
    datasets: [
        {
            label: 'Daily Profit in Satoshis',
            backgroundColor: '#79f879',
            data: props.profits
        },
        {
            label: 'Daily Profit in GBP at current exchange rate',
            backgroundColor: '#7979f8',
            data: props.profitsInGBP
        }
    ]
}
const optionsLine = {
    responsive: true,
    maintainAspectRatio: false
}

</script>

<template>
    <Head title="Graphs"/>

    <GuestLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow-sm sm:rounded-lg">
                    <Bar class="!h-64" :data="data" :options="options"/>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <Line :data="dataLine" :options="optionsLine"/>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
