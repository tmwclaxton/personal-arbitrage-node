<template>
    <div class="w-full overflow-x-scroll containe table-auto border-zinc-700 border-2  rounded-lg rounded shadow-lg   overflow-hidden ">
        <table class="p-6 overflow-hidden w-full">
            <thead>
            <tr>
                <th v-for="heading in headings" class="px-4 py-2 border-b-2 border-zinc-700">{{ heading }}</th>
            </tr>
            </thead>
            <tbody class=" ">
            <tr v-for="row in rows" :key="row.id">
                <td v-for="(value, key) in row" :key="key" class="border px-4 py-2 border-zinc-700" >
                    <!-- if the key is "id", render a link to the endpoint -->
                    <!--<template v-if="['id', 'paper id'].includes(key.toLowerCase())">-->
                    <!--    <BlueLink class="font-mono" v-if="value" :href="`${endpoint}/${value}`">{{ value }}</BlueLink>-->
                    <!--</template>-->

                    <!-- if the key is "value" or "status", render a colored dot -->
                    <template v-if="value != null && ['value', 'ft value', 't&a value', 'h t&a', 'h ft'].includes(key.toLowerCase()) ">
<!--                        check if not array-->
                        <div v-if="!Array.isArray(value)">
                            <div v-if="['no', 'never ran'].includes(value.toLowerCase())" class="flex items-center justify-between space-x-2">
                                <p>{{ value }}</p>
                                <!--<div class="bg-red-600 w-4 h-4 rounded-full"></div>-->
                            </div>
                            <div v-else-if="['yes', 'complete'].includes(value.toLowerCase())" class="flex items-center justify-between space-x-2">
                                <p>{{ value }}</p>
                                <!--<div class="bg-green-500 w-4 h-4 rounded-full"></div>-->
                            </div>
                            <div v-else class="flex items-center justify-between space-x-2">
                                <p>{{ value }}</p>
                                <!--<div class="bg-amber-500 w-4 h-4 rounded-full"></div>-->
                            </div>
                        </div>
                        <div v-else>
<!--                            if has different answers in array, show split coloured dot-->
<!--                            otherwise have normal dot-->
                            <div class="flex items-center justify-between space-x-2">

                                <p>mixed</p>
                                <div class="flex rounded-full overflow-hidden w-4">
                                    <div v-if="value.includes('yes')" class="bg-green-500 w-2 h-4"></div>
                                    <div v-if="value.includes('no')" class="bg-red-500 w-2 h-4"></div>
                                    <div v-if="value.includes('maybe')" class="bg-amber-500 w-2 h-4"></div>
                                </div>
                            </div>

                        </div>
                    </template>
                    <!-- otherwise, render the value as plain text -->
                    <template v-else class="">
                        <p class="max-w-64 break-words text-center mx-auto max-h-24 overflow-y-scroll">{{ value }}</p>
                    </template>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
export default {
    name: "Table",
    props: {
        rows: {
            type: Array,
            required: true,
        },
        endpoint: {
            type: String,
            required: true,
        },
    },
    computed: {
        headings() {
            if (this.rows.length === 0) {
                return [];
            }
            return Object.keys(this.rows[0]);
        },
    }
}


</script>

<style scoped>

</style>
