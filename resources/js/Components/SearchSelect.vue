<template class=" w-full">
	<div class="relative" v-if="options">
		
		<!-- Dropdown Input -->
		<input class=""
			   :name="name"
			   @focus="showOptions()"
			   @blur="exit()"
			   @keyup="keyMonitor"
			   v-model="searchFilter"
			   :disabled="disabled"
			   :placeholder="placeholder" />
		
		<!-- Dropdown Menu -->
		<div class="border w-full flex flex-col items-start absolute z-20 bg-white dark:bg-gray-800 rounded-md shadow-lg"
			 v-show="optionsShown">
			<div
			  class="w-full border cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 p-2"
			  @mousedown="selectOption(option)"
			  v-for="(option, index) in filteredOptions"
			  :key="index">
				· {{ option }}
			</div>
		</div>
	</div>
</template>

<script>
export default {
	name: 'SearchSelect',
	template: 'SearchSelect',
	props: {
		name: {
			type: String,
			required: false,
			default: 'dropdown',
			note: 'Input name'
		},
		options: {
			type: Array,
			required: true,
			default: [],
			note: 'Options of dropdown. An array of strings'
		},
		placeholder: {
			type: String,
			required: false,
			default: 'Please select an option',
			note: 'Placeholder of dropdown'
		},
		disabled: {
			type: Boolean,
			required: false,
			default: false,
			note: 'Disable the dropdown'
		},
		maxItem: {
			type: Number,
			required: false,
			default: 6,
			note: 'Max items showing'
		}
	},
	data() {
		return {
			selected: '',
			optionsShown: false,
			searchFilter: ''
		}
	},
	created() {
		this.$emit('selected', this.selected);
	},
	computed: {
		filteredOptions() {
			const regOption = new RegExp(this.searchFilter, 'ig');
			return this.options
			  .filter(option => this.searchFilter.length < 1 || option.match(regOption))
			  .slice(0, this.maxItem);
		}
	},
	methods: {
		selectOption(option) {
			this.selected = option;
			this.optionsShown = false;
			this.searchFilter = this.selected;
			this.$emit('selected', this.selected);
		},
		showOptions() {
			if (!this.disabled) {
				this.searchFilter = '';
				this.optionsShown = true;
			}
		},
		exit() {
			if (!this.selected) {
				this.selected = '';
				this.searchFilter = '';
			} else {
				this.searchFilter = this.selected;
			}
			this.$emit('selected', this.selected);
			this.optionsShown = false;
		},
		keyMonitor(event) {
			if (event.key === "Enter" && this.filteredOptions[0])
				this.selectOption(this.filteredOptions[0]);
		}
	},
	watch: {
		searchFilter() {
			if (this.filteredOptions.length === 0) {
				this.selected = '';
			} else {
				this.selected = this.filteredOptions[0];
			}
			this.$emit('filter', this.searchFilter);
		}
	}
};
</script>
