<script setup>
import {ref, onMounted, watch} from 'vue';

// Define model and props
const model = defineModel({
	type: String,
	required: true,
});

const props = defineProps({
	disabled: Boolean,
	confidential: {
		type: Boolean,
		default: false, // Confidential input by default
	},
});

const tempValue = ref(model.value);

// on change of tempValue, emit the updated value
watch(tempValue, (value) => {
    model.value = value;
});

// Refs
const input = ref(null);
const showPassword = ref(false); // To toggle visibility

// Focus handling on mounted
onMounted(() => {
	if (input.value.hasAttribute('autofocus')) {
		input.value.focus();
	}
});

// Expose focus method
defineExpose({ focus: () => input.value.focus() });

// Method to toggle visibility
const toggleVisibility = () => {
	showPassword.value = !showPassword.value;
};
</script>

<template>
	<div class=" relative border-gray-300 focus:border-indigo-500 focus:ring-indigo-500
	rounded-md shadow-sm  dark:border-zinc-700 dark:focus:border-zinc-500 dark:focus:ring-zinc-500
bg-white dark:bg-zinc-900 dark:text-white p-0 h-max my-0.5
"
	>
		<input
		  :type="showPassword ? 'text' : (props.confidential ? 'password' : 'text')"
		  v-model="tempValue"
		  class="border-0 focus:ring-0 focus:outline-none  px-2 overflow-scroll
		  bg-white dark:bg-zinc-900 dark:text-white w-full rounded-md m-0 py-0 "
		  :disabled="props.disabled"
		  :class="{ 'opacity-50 cursor-not-allowed': props.disabled, 'pr-8': props.confidential }"
		  ref="input"
		/>
		<!-- Eye icon/button to toggle visibility -->
		<button
		  v-if="props.confidential"
		  @click="toggleVisibility"
		  type="button"
		  class="absolute right-2 inset-y-0 flex items-center justify-center"
		>
			{{ showPassword ? '🙈' : '👁️' }} <!-- Icons to toggle visibility -->
		</button>
	</div>
</template>
