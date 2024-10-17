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
    profitsInFiat: Object,
	averageBuyPremiums: Object,
	averageSellPremiums: Object,
    ratiosBetweenMakeAndTake: Object,
    templateIds: Object,
    templatePopularity: Object,
	primaryCurrency: String
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
            label: 'Daily Profit in ' + props.primaryCurrency + ' at current exchange rate',
            backgroundColor: '#7979f8',
            data: props.profitsInFiat
        }
    ]
}
const optionsLine = {
    responsive: true,
    maintainAspectRatio: false
}

const dataSellPremiums = {
	labels: props.averageSellPremiums.dates,
	datasets: [
		{
			label: 'Daily Average Sell Premium',
			backgroundColor: '#00a5ff',
			data: props.averageSellPremiums.values
		}
	]
}

const optionsSellPremiums = {
	responsive: true,
	maintainAspectRatio: false
}
const dataBuyPremiums = {
	labels: props.averageBuyPremiums.dates,
	datasets: [
		{
			label: 'Daily Average Buy Premium',
			backgroundColor: '#00a5ff',
			data: props.averageBuyPremiums.values
		}
	]
}

const optionsBuyPremiums = {
    responsive: true,
    maintainAspectRatio: false
}

const dataRatios = {
    labels: props.dates,
    datasets: [
        {
            label: 'Ratio between Make and Take (close to 1 is better for volume fees from the provider)',
            backgroundColor: '#f87979',
            data: props.ratiosBetweenMakeAndTake
        }
    ]
}

const optionsRatios = {
    responsive: true,
    maintainAspectRatio: false
}

// bar chart for template popularity
const dataTemplatePopularity = {
    labels: props.templateIds,
    datasets: [
        {
            label: 'Template Popularity',
            backgroundColor: '#f87979',
            data: props.templatePopularity
        }
    ]
}

const optionsTemplatePopularity = {
    responsive: true,
    maintainAspectRatio: false
}


</script>

<template>
    <Head title="Graphs"/>

    <GuestLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!--<div class="overflow-hidden shadow-sm sm:rounded-lg">-->
                <!--    <div class="p-6 bg-white border-b border-gray-200">-->
                <!--        <h1 class="text-2xl font-semibold">Graphs</h1>-->
                <!--    </div>-->
                <!--</div>-->
                <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white">
                    <Bar class="!h-64" :data="data" :options="options"/>
                </div>
				<div class="mt-5 overflow-hidden shadow-sm sm:rounded-lg bg-white">
					<Line class="!h-64"  :data="dataSellPremiums" :options="optionsSellPremiums"/>
				</div>
				<div class="mt-5 overflow-hidden shadow-sm sm:rounded-lg bg-white">
					<Line class="!h-64"  :data="dataBuyPremiums" :options="optionsBuyPremiums"/>
				</div>
                <div class="mt-5 overflow-hidden shadow-sm sm:rounded-lg bg-white">
                    <Line class="!h-64"  :data="dataLine" :options="optionsLine"/>
                </div>
                <div class="mt-5 overflow-hidden shadow-sm sm:rounded-lg bg-white">
                    <Line class="!h-64"  :data="dataRatios" :options="optionsRatios"/>
                </div>
                <div class="mt-5 overflow-hidden shadow-sm sm:rounded-lg bg-white">
                    <Bar class="!h-64" :data="dataTemplatePopularity" :options="optionsTemplatePopularity"/>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
