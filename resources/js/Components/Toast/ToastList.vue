<script setup>
import ToastListItem from "@/Components/Toast/ToastListItem.vue";
import {onMounted, onUnmounted} from "vue";

import {Inertia} from "@inertiajs/inertia";
import {usePage} from "@inertiajs/vue3";
import toastStore from "@/Stores/ToastStore";

// onMounted(() => {
//     toastStore.add({
//         message: "Hello World",
//         type: "success",
//         duration: 300000000,
//     });
// });

const page = usePage();
const name = 'ToastList';
const props = defineProps({
    flash: {
        type: Object,
        required: false,
        default: null
    },
    key: {
        type: String,
        required: false,
        default: null
    }
});

let timeoutId;
let isToastMessageCalled = false;

function toastMessages() {
    if (!isToastMessageCalled) {
        if (props.flash.message.error && props.flash.message.error.length > 0) {
            toastStore.add({
                message: props.flash.message.error,
                type: "error",
            });
        }
        if (props.flash.message.success && props.flash.message.success.length > 0) {
            toastStore.add({
                message: props.flash.message.success,
                type: "success",
            });
        }
        if (props.flash.message.status && props.flash.message.status.length > 0) {
            toastStore.add({
                message: props.flash.message.status,
                type: "normal",
            });
        }
        isToastMessageCalled = true;
    }
}

let removeSuccessEventListener = Inertia.on("success", () => {
    if (timeoutId) {
        clearTimeout(timeoutId);
    }
    timeoutId = setTimeout(() => {
        toastMessages();
        isToastMessageCalled = false;
    }, 50);
});
let removeNavigateEventListener = Inertia.on("navigate", () => {
    if (timeoutId) {
        clearTimeout(timeoutId);
    }
    timeoutId = setTimeout(() => {
        toastMessages();
        isToastMessageCalled = false;
    }, 50);
});

onUnmounted(() => {
    removeSuccessEventListener();
    removeNavigateEventListener();
});

function remove(index) {
    toastStore.remove(index);
}
</script>
<template>
    <TransitionGroup
        tag="div"
        enter-from-class="translate-x-full opacity-0"
        enter-active-class="duration-500"
        leave-active-class="duration-500"
        leave-to-class="translate-x-full opacity-0"
        class="fixed bottom-4 right-4 z-50 w-full max-w-xs space-y-4">
        <ToastListItem
            v-for="(item, index) in toastStore.items"
            :key="item.key"
            :message="item.message"
            :type="item.type"
            :duration="3000"
            @remove="remove(index)"
        />
    </TransitionGroup>
</template>
