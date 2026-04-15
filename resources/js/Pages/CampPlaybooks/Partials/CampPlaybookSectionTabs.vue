<script setup>
const props = defineProps({
    sections: { type: Array, required: true },
    activeSectionIndex: { type: Number, required: true },
});

const emit = defineEmits(['update:activeSectionIndex']);
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <button
            v-for="(section, idx) in props.sections"
            :key="`playbook-section-${idx}`"
            type="button"
            class="rounded-md border px-3 py-1.5 text-sm"
            :class="idx === props.activeSectionIndex ? 'border-brand-blue bg-brand-blue/10 text-app-ink dark:text-app-ink-dark' : 'border-app-border bg-white text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark'"
            @click="emit('update:activeSectionIndex', idx)"
        >
            {{ section.title || `Sectie ${idx + 1}` }}
        </button>
    </div>
</template>
