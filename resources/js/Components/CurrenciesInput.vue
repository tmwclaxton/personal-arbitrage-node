
<script setup>

import {computed, onMounted, ref, watch} from "vue";
const name = 'CurrenciesInput';
const props = defineProps({
    payment_methods: {
        type: Array,
        default: ''
    },
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
        <p class="my-1 mt-2 text-md font-bold">Accepted Currencies</p>
        <div class=" select-none flex flex-row gap-x-5 flex-wrap ">
            <div class="flex flex-row gap-2 items-center">
                <input type="checkbox" id="USD" value="USD" v-model="payment_methodsLocal"
                       :checked="payment_methodsLocal.includes('USD')"
                       class="w-4 h-4 text-blue-600 bg-zinc-100 border-zinc-300 without-ring dark:bg-zinc-700 dark:border-zinc-600 hover:dark:bg-zinc-700 focus:dark:bg-zinc-700">
                <label for="USD" class="flex flex-row gap-x-2 align-middle items-center">
                    <span class="font-semibold text-sm">USD</span>
                </label>
            </div>
            <div class="flex flex-row gap-2 items-center">
                <input type="checkbox" id="GBP" value="GBP" v-model="payment_methodsLocal"
                       :checked="payment_methodsLocal.includes('GBP')"
                       class="w-4 h-4 text-blue-600 bg-zinc-100 border-zinc-300 without-ring dark:bg-zinc-700 dark:border-zinc-600 hover:dark:bg-zinc-700 focus:dark:bg-zinc-700">
                <label for="GBP" class="flex flex-row gap-x-2  align-middle items-center">
                    <span class="font-semibold text-sm">GBP</span>
                </label>
            </div>
            <div class="flex flex-row gap-2 items-center">
                <input type="checkbox" id="EUR" value="EUR" v-model="payment_methodsLocal"
                       :checked="payment_methodsLocal.includes('EUR')"
                       class="w-4 h-4 text-blue-600 bg-zinc-100 border-zinc-300 without-ring dark:bg-zinc-700 dark:border-zinc-600 hover:dark:bg-zinc-700 focus:dark:bg-zinc-700">
                <label for="EUR" class="flex flex-row gap-x-2 align-middle items-center">
                    <span class="font-semibold text-sm">EUR</span>
                </label>
            </div>
            <div class="flex flex-row gap-2 items-center">
                <input type="checkbox" id="BRL" value="BRL" v-model="payment_methodsLocal"
                       :checked="payment_methodsLocal.includes('BRL')"
                       class="w-4 h-4 text-blue-600 bg-zinc-100 border-zinc-300 without-ring dark:bg-zinc-700 dark:border-zinc-600 hover:dark:bg-zinc-700 focus:dark:bg-zinc-700">
                <label for="EUR" class="flex flex-row gap-x-2 align-middle items-center">
                    <span class="font-semibold text-sm">BRL</span>
                </label>
            </div>
        </div>
    </div>

</template>



