
<script setup>
import { Link } from '@inertiajs/vue3';

import {computed, onMounted, ref, watch} from "vue";
import {router} from "@inertiajs/vue3";
const name = 'PaymentsInput';
const props = defineProps({
    payment_methods: {
        type: Array,
        default: ''
    },
    options: {
        type: Array,
        default: ['Revolut', 'Paypal Friends & Family', 'Strike', 'Wise', 'Faster Payments', 'Instant SEPA']
    }
});

// if options is null but there is a payment method enabled
const combinedOptions = computed(() => {
	if ((props.options === null || props.options.length === 0) && props.payment_methods !== null && props.payment_methods.length > 0) {
		return props.payment_methods;
	}
	if ((props.payment_methods === null || props.payment_methods.length === 0) && props.options !== null && props.options.length > 0) {
		return props.options;
	}
	if (props.options === null || props.options.length === 0) {
		return [];
	}
	// else return the options and payment methods combined (distinct)
	return [...new Set([...props.options, ...props.payment_methods])];
});


const emits = defineEmits(['update:modelValue']);

const submit = () => {
    emits('update:modelValue');
}

const payment_methodsLocal = ref(props.payment_methods || []);

watch(payment_methodsLocal, () => {
    emits('update:modelValue', payment_methodsLocal.value);
});


</script>

<template>
    <div class="flex flex-col gap-y-2 ">
        <!--<p class="my-1 mt-2 text-md font-bold">Payment Methods</p>-->
        <div class=" select-none flex flex-row gap-x-5 flex-wrap ">
            <div v-if="combinedOptions.length > 0"
			  v-for="option in combinedOptions" :key="option" class="flex flex-row gap-2 items-center">
                <input type="checkbox" :id="option" :value="option" v-model="payment_methodsLocal"
                       :checked="payment_methodsLocal.includes(option)"
                       class="w-4 h-4 text-blue-600 bg-zinc-100 border-zinc-300 without-ring dark:bg-zinc-700 dark:border-zinc-600 hover:dark:bg-zinc-700 focus:dark:bg-zinc-700">
                <label :for="option" class="flex flex-row gap-x-2 align-middle items-center">
                    <span class="font-semibold text-sm">{{ option }}</span>
                </label>
            </div>
			<div v-if="combinedOptions.length === 0" class="flex flex-row gap-2 items-center">
				<p class="text-sm text-gray-400">No payment methods available
					<Link :href="route('dashboard.index')"
						  class="text-blue-600 cursor-pointer hover:underline" >Enable them on the config page!</Link>
				</p>
			</div>
        </div>
    </div>

</template>



