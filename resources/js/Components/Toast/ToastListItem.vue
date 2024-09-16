<script setup>
import {onMounted} from "vue";

import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";


const props = defineProps({
    message: String,
    type: {
        type: String,
        default: "normal"
    },
    duration: {
        type: Number,
        default: 8000
    }
});

onMounted(() => {
    setTimeout(() => emit("remove"), props.duration);
});

const emit = defineEmits(["remove"]);
</script>
<template>
    <div
        class="flex items-center rounded-lg p-4 shadow shadow-md shadow-zinc-900 bg-slate-900 text-white"
        role="alert"
    >
        <div
            class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg
             "
            :class="{
          ' bg-green-900 text-white': props.type === 'success',
          ' bg-red-800 text-red-100': props.type === 'error'
        }"

        >
            <!--<font-awesome-icon v-if="props.type === 'normal'" class="   aspect-square  mx-auto"-->
            <!--                   :icon="['fas', 'message']" />-->
            <!--<font-awesome-icon  v-if="props.type === 'undo'" class="aspect-square  mx-auto"-->
            <!--                    :icon="['fas', 'rotate-left']"/>-->
            <!--<font-awesome-icon v-if="props.type === 'success'" class="   aspect-square  mx-auto"-->
            <!--                   :icon="['fas', 'check']" />-->
			<svg v-if="props.type === 'success'" class=" w-4 aspect-square  mx-auto"
			  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg>
			<svg v-if="props.type === 'error'" class=" w-4  aspect-square  mx-auto"
			  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 32c14.2 0 27.3 7.5 34.5 19.8l216 368c7.3 12.4 7.3 27.7 .2 40.1S486.3 480 472 480L40 480c-14.3 0-27.6-7.7-34.7-20.1s-7-27.8 .2-40.1l216-368C228.7 39.5 241.8 32 256 32zm0 128c-13.3 0-24 10.7-24 24l0 112c0 13.3 10.7 24 24 24s24-10.7 24-24l0-112c0-13.3-10.7-24-24-24zm32 224a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>
         </div>
        <div class="ml-3 text-sm font-normal">{{ props.message }}</div>
        <button
            @click="emit('remove')"
            type="button"
            class="-mx-1.5 -my-1.5 ml-auto inline-flex h-8 w-8 rounded-lg bg-transparent p-1.5 text-white hover:bg-zinc-800 hover:text-white"
            data-dismiss-target="#toast-default"
            aria-label="Close"
        >
            <span class="sr-only">Close</span>
            <!--<font-awesome-icon class=" h-5 w-5 "  :icon="['fas', 'times']" />-->
			
			<svg class=" h-5 w-5 " fill="white"
				 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
				<path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/>
			</svg>
        </button>
    </div>
</template>
