<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap gap-1 items-center justify-center py-6">
        <template v-for="(link, key) in links" :key="key">
            <div v-if="link.url === null" 
                class="px-4 py-2 text-sm font-bold text-gray-400 bg-gray-50 border border-gray-200 rounded-xl cursor-not-allowed"
                v-html="link.label" />
            <Link v-else 
                :href="link.url" 
                class="px-4 py-2 text-sm font-bold border rounded-xl transition-all"
                :class="{ 'bg-gradient-to-r from-green-500 to-green-600 text-white border-green-600 shadow-md': link.active, 'bg-white text-gray-600 border-gray-200 hover:bg-green-50 hover:text-green-700 hover:border-green-200': !link.active }"
                v-html="link.label" />
        </template>
    </div>
</template>
