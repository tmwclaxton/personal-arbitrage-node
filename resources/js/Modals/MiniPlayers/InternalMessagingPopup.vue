<script setup>
import {onMounted, onUnmounted, ref, watch} from "vue";

import {debounce, round} from "lodash";
// const internalMessagingStore = useInternalMessagingStore();
const expandQueue = ref(false);
const name = "InternalMessagingPopup";

const draggableDiv = ref(null);
let offsetX = 0;
let offsetY = 0;
let isDragging = false;

let initialX = 0;
let initialY = 0;


onMounted(() => {

 // using top and left position the initial position of the draggable div 15px fro mthe bottom right corner
    draggableDiv.value.style.left = (window.innerWidth - 384 - 15) + 'px';
    draggableDiv.value.style.top = (window.innerHeight - 348 - 15) + 'px';

    draggableDiv.value.addEventListener('mousedown', (event) => {
        event.preventDefault();
        initialX = event.clientX;
        initialY = event.clientY;
        isDragging = true;
    });

    document.addEventListener('mousemove', (event) => {
        if (isDragging) {
            event.preventDefault();

            const deltaX = event.clientX - initialX;
            const deltaY = event.clientY - initialY;

            const newLeft = parseInt(draggableDiv.value.style.left) + deltaX;
            const newTop = parseInt(draggableDiv.value.style.top) + deltaY;

            const maxX = window.innerWidth - draggableDiv.value.offsetWidth - 15;
            const maxY = window.innerHeight - draggableDiv.value.offsetHeight - 15;
            const clampedLeft = Math.max(15, Math.min(newLeft, maxX));
            const clampedTop = Math.max(15, Math.min(newTop, maxY));


            draggableDiv.value.style.left = clampedLeft + 'px';
            draggableDiv.value.style.top = clampedTop + 'px';

            initialX = event.clientX;
            initialY = event.clientY;
        }
    });

    document.addEventListener('mouseup', () => {
        isDragging = false;
    });

    window.addEventListener('resize', () => {
        checkIfInViewport();

    });

});

onUnmounted( () => {
    window.removeEventListener('resize', () => {
        checkIfInViewport();
    });
    window.removeEventListener('mouseup', () => {
        isDragging = false;
    });
});

const checkIfInViewport = debounce(() => {
    setTimeout(() => {
        if (!draggableDiv.value) return;
        console.log('checkIfInViewport');
        const rect = draggableDiv.value.getBoundingClientRect();
        if (!draggableDiv.value) return;
        const isInViewport =
            rect.top >= 15 &&
            rect.left >= 15 &&
            rect.bottom <= window.innerHeight - 15 &&
            rect.right <= window.innerWidth - 15;

        if (!isInViewport) {
            const maxX = window.innerWidth - draggableDiv.value.offsetWidth - 15;
            const maxY = window.innerHeight - draggableDiv.value.offsetHeight - 15;

            const clampedLeft = Math.max(15, Math.min(rect.left, maxX));
            const clampedTop = Math.max(15, Math.min(rect.top, maxY));

            draggableDiv.value.style.left = clampedLeft + 'px';
            draggableDiv.value.style.top = clampedTop + 'px';
        }
    }, 100);
}, 100);



const toggleExpandQueue = () => {
    expandQueue.value = !expandQueue.value;
    // wait for the animation to finish
    checkIfInViewport();
};


// watch for changes in the length of the queue
// watch(() => queueStore.items.length, () => {
//     if (queueStore.items.length > 1) {
//         // wait for the animation to finish
//         checkIfInViewport();
//     }
// });




</script>

<template>
    <div ref="draggableDiv"   class="z-40 fixed shadow-md bg-white dark:bg-zinc-900 border-2 border-purple-200 dark:border-purple-500
    rounded-xl overflow-hidden flex flex-col w-96" >
<!--         v-bind:class="useQueueStore().showMiniPlayer ? '' : 'opacity-0 w-0 h-0 pointer-events-none' "-->

<!--        <div class="my-0.5 border border-zinc-200 dark:border-zinc-800" v-if="expandQueue"/>-->
<!--        <div  id="miniPlayerItemsHolder" class="relative flex flex-col pb-1 max-h-48 overflow-y-auto" v-if="expandQueue">-->
<!--        </div>-->

        <p class="text-center text-lg text-gray-500 dark:text-gray-400 font-bold">Internal Messaging</p>

    </div>

</template>


