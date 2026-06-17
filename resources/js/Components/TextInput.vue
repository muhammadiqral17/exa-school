<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="bg-gray-50 border border-gray-200 text-gray-800 rounded-2xl py-4 px-6 focus:bg-white focus:border-green-400 focus:ring-4 focus:ring-green-500/20 transition-all font-bold placeholder:text-gray-400 w-full"
        v-model="model"
        ref="input"
    />
</template>
