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
        class="rounded-md border-app-border bg-app-panel shadow-sm text-app-ink placeholder:text-app-muted focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/35 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted-dark dark:focus:border-brand-blue-light dark:focus:ring-2 dark:focus:ring-brand-blue/40"
        v-model="model"
        ref="input"
    />
</template>
