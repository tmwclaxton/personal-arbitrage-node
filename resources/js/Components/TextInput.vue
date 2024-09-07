<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });

const props = defineProps({
    disabled: Boolean,
});
</script>

<template>
    <input
        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm bg-white dark:bg-black dark:text-white dark:border-zinc-700 dark:focus:border-zinc-500 dark:focus:ring-zinc-500"
        v-model="model"
        :disabled="props.disabled"
        :class="{ 'opacity-50 cursor-not-allowed': props.disabled }"
        ref="input"
    />
</template>
