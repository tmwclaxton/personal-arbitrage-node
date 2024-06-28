<template>
    <button
        :class="[
      'relative inline-flex items-center rounded-full transition-colors focus:outline-none',
      sizeClass,
      { [activeColor]: isActive, [inactiveColor]: !isActive }
    ]"
        @click="toggle"
    >
    <span
        :class="[
        'bg-white transform transition-transform ease-in-out duration-200 inline-block rounded-full',
        sizeBallClass,
        { [ballActivePosition]: isActive, [ballInactivePosition]: !isActive }
      ]"
    ></span>
    </button>
</template>

<script>
import { defineComponent, ref, computed, toRefs } from 'vue';

export default defineComponent({
    name: 'ToggleButton',
    props: {
        modelValue: {
            type: Boolean,
            default: false
        },
        activeColor: {
            type: String,
            default: 'bg-blue-500'
        },
        inactiveColor: {
            type: String,
            default: 'bg-gray-300'
        },
        size: {
            type: String,
            default: 'md'
        }
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const { modelValue, activeColor, inactiveColor, size } = toRefs(props);
        const isActive = ref(modelValue.value);

        const toggle = () => {
            isActive.value = !isActive.value;
            emit('update:modelValue', isActive.value);
        };

        const sizeClass = computed(() => {
            switch (size.value) {
                case 'sm':
                    return 'h-4 w-8';
                case 'lg':
                    return 'h-8 w-16';
                default:
                    return 'h-6 w-11';
            }
        });

        const sizeBallClass = computed(() => {
            switch (size.value) {
                case 'sm':
                    return 'w-3 h-3';
                case 'lg':
                    return 'w-7 h-7';
                default:
                    return 'w-4 h-4';
            }
        });

        const ballActivePosition = computed(() => {
            switch (size.value) {
                case 'sm':
                    return 'translate-x-4';
                case 'lg':
                    return 'translate-x-8';
                default:
                    return 'translate-x-6';
            }
        });

        const ballInactivePosition = computed(() => {
            switch (size.value) {
                case 'sm':
                    return 'translate-x-1';
                case 'lg':
                    return 'translate-x-1';
                default:
                    return 'translate-x-1';
            }
        });

        return {
            isActive,
            toggle,
            activeColor,
            inactiveColor,
            sizeClass,
            sizeBallClass,
            ballActivePosition,
            ballInactivePosition
        };
    }
});
</script>

<style scoped>
/* Additional styles if needed */
</style>
