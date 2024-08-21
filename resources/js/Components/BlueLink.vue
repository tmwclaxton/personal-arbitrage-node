<template>
    <Link
        v-if="this.method == null || this.method == 'get'"
        class="text-lg text-sky-500 hover:text-sky-300 focus:outline-none focus:text-sky-300"
        :href="href"
        :method="method"
    ><slot />
    </Link>

    <Link
        v-else
        class="text-lg text-sky-500 hover:text-sky-300 focus:outline-none focus:text-sky-300"
        :href="href"
        :method="method"
        as="button"
    >
        <slot />
    </Link>
</template>

<script>
export default {
    props: {
        href: String,
        method: String,
    }
};
</script>
