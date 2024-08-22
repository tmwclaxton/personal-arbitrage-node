<template>
    <div>
        <a
        :href="link.url"
        v-for="link in links"
        v-html="link.label"
        class="px-1"
        :class="{' ' : !link.url, 'font-bold underline' : link.active}"
        />
    </div>
</template>

<script setup>
import { defineProps } from 'vue';
const props = defineProps({
    links: Array
})

</script>
