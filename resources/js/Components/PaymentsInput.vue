
<script setup>

import {computed, onMounted, ref, watch} from "vue";
const name = 'PaymentsInput';
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
        <p class="my-1 mt-2 text-md font-bold">Payment Methods</p>
        <div class=" select-none flex flex-row gap-x-5 flex-wrap ">
            <div class="flex flex-row gap-2 items-center">
                <input type="checkbox" id="Revolut" value="Revolut" v-model="payment_methodsLocal"
                       :checked="payment_methodsLocal.includes('Revolut')"
                       class="w-4 h-4 text-blue-600 bg-zinc-100 border-zinc-300 without-ring dark:bg-zinc-700 dark:border-zinc-600 hover:dark:bg-zinc-700 focus:dark:bg-zinc-700">
                <label for="Revolut" class="flex flex-row gap-x-2 align-middle items-center">
                    <span class="font-semibold text-sm">Revolut</span>
                </label>
            </div>
            <div class="flex flex-row gap-2 items-center">
                <input type="checkbox" id="Paypal Friends & Family" value="Paypal Friends & Family" v-model="payment_methodsLocal"
                       :checked="payment_methodsLocal.includes('Paypal Friends & Family')"
                       class="w-4 h-4 text-blue-600 bg-zinc-100 border-zinc-300 without-ring dark:bg-zinc-700 dark:border-zinc-600 hover:dark:bg-zinc-700 focus:dark:bg-zinc-700">
                <label for="Paypal Friends & Family" class="flex flex-row gap-x-2  align-middle items-center">
                    <span class="font-semibold text-sm">Paypal Friends & Family</span>
                </label>
            </div>
            <div class="flex flex-row gap-2 items-center">
                <input type="checkbox" id="Strike" value="Strike" v-model="payment_methodsLocal"
                       :checked="payment_methodsLocal.includes('Strike')"
                       class="w-4 h-4 text-blue-600 bg-zinc-100 border-zinc-300 without-ring dark:bg-zinc-700 dark:border-zinc-600 hover:dark:bg-zinc-700 focus:dark:bg-zinc-700">
                <label for="Strike" class="flex flex-row gap-x-2 align-middle items-center">
                    <span class="font-semibold text-sm">Strike</span>
                </label>
            </div>
            <div class="flex flex-row gap-2 items-center">
                <input type="checkbox" id="Wise" value="Wise" v-model="payment_methodsLocal"
                       :checked="payment_methodsLocal.includes('Wise')"
                       class="w-4 h-4 text-blue-600 bg-zinc-100 border-zinc-300 without-ring dark:bg-zinc-700 dark:border-zinc-600 hover:dark:bg-zinc-700 focus:dark:bg-zinc-700">
                <label for="Wise" class="flex flex-row gap-x-2 align-middle items-center">
                    <span class="font-semibold text-sm">Wise</span>
                </label>
            </div>
        </div>
    </div>

</template>



