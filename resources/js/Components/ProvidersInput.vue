
<script setup>

import {computed, onMounted, ref, watch} from "vue";
const name = 'PaymentsInput';
const props = defineProps({
    providers: {
        type: Array,
        default: ''
    },
});

const emits = defineEmits(['update:modelValue']);

const submit = () => {
    emits('update:modelValue');
}

const providersLocal = ref(props.providers || []);

watch(providersLocal, () => {
    emits('update:modelValue', providersLocal.value);
});

const options = ['satstralia', 'temple', 'lake', 'veneto', 'exp'];

</script>

<template>
    <div class="flex flex-col gap-y-2 ">
        <p class="my-1 mt-2 text-md font-bold">Provider</p>
        <div class=" select-none flex flex-row gap-x-5 flex-wrap ">
            <div v-for="option in options" :key="option" class="flex flex-row gap-2 items-center">
                <input type="checkbox" :id="option" :value="option" v-model="providersLocal"
                       :checked="providersLocal.includes(option)"
                       class="w-4 h-4 text-blue-600 bg-zinc-100 border-zinc-300 without-ring dark:bg-zinc-700 dark:border-zinc-600 hover:dark:bg-zinc-700 focus:dark:bg-zinc-700">
                <label :for="option" class="flex flex-row gap-x-2 align-middle items-center">
                    <span class="font-semibold text-sm">{{ option }}</span>
                </label>
            </div>

        </div>
    </div>

</template>



