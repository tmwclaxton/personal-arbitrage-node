

<script setup>

 import {useConfirmModalStore} from "@/Stores/ConfirmModelStore";
 import SecondaryButton from "@/Components/SecondaryButton.vue";
 import PrimaryButton from "@/Components/PrimaryButton.vue";
 import TextInput from "@/Components/TextInput.vue";
 import {ref} from "vue";
 const confirmModalStore = useConfirmModalStore();
 const name = 'ConfirmModal';
 const refresh = ref(0);

 // Update the amount in paragraph when slider is moved
const updateParagraph = (event) => {
    useConfirmModalStore().amount = event.target.value;
    refresh.value++;
}


</script>

<template>

    <div
        v-show="confirmModalStore.show" class="z-50 absolute left-1/2 right-1/2 flex-grow h-max flex flex-row justify-center">
        <div class="fixed my-auto inset-y-0 h-max bg-zinc-100 dark:bg-zinc-800
        rounded-xl select-none w-96">
            <!--Close modal-->
            <button @click="confirmModalStore.show = false" type="button" class="absolute top-3 right-2.5  p-1.5 ml-auto inline-flex items-center
            text-sm text-zinc-400 hover:text-zinc-900 dark:hover:text-white
            bg-transparent hover:bg-zinc-200 dark:hover:bg-zinc-800 rounded-lg " data-modal-hide="popup-modal">
                <!--<font-awesome-icon :icon="['fas', 'xmark']" class="w-5 h-5"  />-->
                <span class="sr-only">Close modal</span>
            </button>
            <div class="p-6 text-center">
                <!--<font-awesome-icon :icon="['fass', 'circle-exclamation']"  class="mx-auto mb-4 text-zinc-400 w-14 h-14 dark:text-zinc-200" />-->
                <h3 class="  text-lg font-normal text-zinc-500 dark:text-zinc-400" v-text="confirmModalStore.title"/>
            </div>

			<!-- Amount -->
			<div v-if="confirmModalStore.slider === true" class="flex flex-col gap-y-1 mx-5 mb-2">
				<input
				  v-model="confirmModalStore.amount"
                  v-on:input="updateParagraph($event)"
				  type="range"
				  :min="confirmModalStore.sliderMin"
				  :max="confirmModalStore.sliderMax"
				  class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
				<div :key="refresh" class="flex flex-row justify-between px-5">
					<p class="text-sm text-zinc-500 dark:text-zinc-400 w-full my-auto"  v-text="confirmModalStore.sliderMin"/>
					<text-input v-if="confirmModalStore.amount" v-model="confirmModalStore.amount" class=" w-40 flex-shrink h-8 text-center mx-auto " />
					<p class="text-sm text-zinc-500 dark:text-zinc-400 w-full text-right my-auto" v-text="confirmModalStore.sliderMax"/>
				</div>
			</div>

			<div class="flex flex-row justify-center gap-2 px-5 pb-5">
                <SecondaryButton class="w-full" @click="confirmModalStore.clickButtonOne">
                    <p class="mx-auto font-bold" v-text="confirmModalStore.buttonOneText"/>
                </SecondaryButton>
                <PrimaryButton class="w-full" @click="confirmModalStore.clickButtonTwo">
                    <p class="mx-auto font-bold" v-text="confirmModalStore.buttonTwoText"/>
                </PrimaryButton>
            </div>
        </div>
    </div>
</template>
