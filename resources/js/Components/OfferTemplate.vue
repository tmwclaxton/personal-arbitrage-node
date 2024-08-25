<script setup>


const props = defineProps({
    template: Object,
});
</script>

<template>
    <div :key="template.id"
         class="rounded-lg border border-gray-200 w-full my-2 p-2 bg-white dark:bg-zinc-900">
        <p class="font-bold" v-text="'Template ID: ' + template.id"></p>
        <div class="grid grid-cols-4 border-t border-gray-200 p-2">
            <p>
                <span class="font-bold" v-text="template.max_amount && template.max_amount > 0 ? 'Min: ' + template.min_amount + ' - ' : 'Amount: '"></span>
                {{template.min_amount }}
            </p>
            <p v-if="template.max_amount && template.max_amount > 0">
                <span class="font-bold">Max: </span>
                {{template.max_amount }}
            </p>

            <p>
                <span class="font-bold">Premium: </span>
                {{template.premium }}
            </p>

            <p>
                <span class="font-bold">Bond Size: </span>
                {{template.bond_size }}
            </p>

            <p>
                <span class="font-bold">Currency: </span>
                {{template.currency }}
            </p>

            <p>
                <span class="font-bold">Auto Create: </span>
                {{template.auto_create }}
            </p>

            <p class="col-span-4 my-1">
                <span class="font-bold">Payment Methods: </span>
                {{template.payment_methods }}
            </p>

            <p class="col-span-4">
                <span class="font-bold">Provider: </span>
                {{template.provider }}
            </p>


        </div>
    </div>
</template>

<style scoped>

</style>
