
<script setup>

import {computed, onMounted, ref, watch} from "vue";
const name = 'CurrenciesInput';
const props = defineProps({
    payment_methods: {
        type: Array,
        default: ''
    },
    currencies: {
        type: Array,
        default: ['USD', 'GBP', 'EUR', 'BRL']
    }
});

const emits = defineEmits(['update:modelValue']);

const submit = () => {
    emits('update:modelValue');
}

const payment_methodsLocal = ref(props.payment_methods || []);

watch(payment_methodsLocal, () => {
    emits('update:modelValue', payment_methodsLocal.value);
});

const randomKey = () => {
	return "key-" + Math.random().toString(36).slice(2, 9);
}


</script>

<template>
    <div class="flex flex-col gap-y-2 ">
        <div class=" select-none flex flex-row gap-x-5 flex-wrap max-w-3xl">
            <div v-for="currency in currencies" :key="currency" class="flex flex-row gap-2 items-center w-16">
                <input key="{{randomKey()}}"
				  type="checkbox" :id="currency" :value="currency" v-model="payment_methodsLocal"
                       :checked="payment_methodsLocal.includes(currency)"
                       class="w-4 h-4 text-blue-600 bg-zinc-100 border-zinc-300 without-ring dark:bg-zinc-700 dark:border-zinc-600 hover:dark:bg-zinc-700 focus:dark:bg-zinc-700">
                <label :for="currency" class="flex flex-row gap-x-2 align-middle items-center">
                    <span class="font-semibold text-sm">{{currency}}</span>
                </label>
            </div>
        </div>
    </div>

</template>



