<template>

    <Link
        class="text-lg text-sky-500 hover:text-sky-300 focus:outline-none focus:text-sky-300"
        :href="href"
    >
        <slot />
    </Link>
</template>

<script setup>
import {Head, Link, router} from '@inertiajs/vue3';

const props = defineProps({
	href: String,
});
</script>
