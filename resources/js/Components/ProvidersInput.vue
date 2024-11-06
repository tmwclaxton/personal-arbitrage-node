<script setup>
import {ref, watch} from "vue";

const name = 'PaymentsInput';

const props = defineProps({
	providers: {
		type: Array,
		default: ''
	},
	options: {
		type: Array,
		default: ['temple', 'lake', 'veneto']
	}
});

const emits = defineEmits(['update:modelValue']);

// Initialize providersLocal with props.providers or an empty array
const providersLocal = ref(props.providers ? [...props.providers] : []);

// Emit the updated providersLocal whenever it changes
watch(providersLocal, () => {
	emits('update:modelValue', providersLocal.value);
});

// Submit function to emit the updated model value
const submit = () => {
	emits('update:modelValue');
};
</script>

<template>
	<div class="flex flex-col gap-y-2 ">
		<!--<p class="my-1 mt-2 text-md font-bold">Provider</p>-->
		<div class="select-none flex flex-row gap-x-5 flex-wrap">
			<div v-for="(option, index) in options" :key="option" class="flex flex-row gap-2 items-center">
				<!-- Custom Checkbox for each option -->
				<input
				  type="checkbox"
				  :id="option"
				  :value="option"
				  v-model="providersLocal"
				  :checked="providersLocal.includes(option)"
				  class="w-4 h-4 text-blue-600 bg-zinc-100 border-zinc-300 dark:bg-zinc-700 dark:border-zinc-600">

				<!-- Label showing option name and its position in the providersLocal array -->
				<label :for="option" class="flex flex-row gap-x-2 align-middle items-center">
					<span class="font-semibold text-sm">{{ option }}</span>
					<span v-if="providersLocal.includes(option)">
                        ({{ providersLocal.indexOf(option) + 1 }})
                    </span>
				</label>
			</div>
		</div>
	</div>
</template>
