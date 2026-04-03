<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-md border-2 border-brand-blue bg-transparent px-4 py-2 text-xs font-semibold uppercase tracking-widest text-brand-blue-dark shadow-sm transition duration-150 ease-in-out hover:bg-brand-blue/10 focus:outline-none focus:ring-2 focus:ring-brand-blue/50 focus:ring-offset-2 focus:ring-offset-app-canvas disabled:opacity-25 dark:border-brand-blue-light dark:text-brand-blue-light dark:hover:bg-brand-blue/20 dark:focus:ring-offset-app-canvas-dark"
    >
        <slot />
    </button>
</template>
