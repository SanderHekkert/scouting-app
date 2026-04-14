<script setup>
defineProps({
    contentClass: {
        type: String,
        default: 'px-6 py-8 sm:px-10 sm:py-10',
    },
});
</script>

<template>
    <div
        class="relative w-full overflow-hidden rounded-2xl border border-white/20 bg-white/95 shadow-2xl shadow-brand-blue/15 backdrop-blur-md dark:border-brand-blue/35 dark:bg-slate-900/90"
    >
        <div class="rainbow-animate h-1 w-full bg-gradient-to-r from-brand-red via-brand-yellow to-brand-blue" aria-hidden="true" />
        <div :class="contentClass">
            <slot />
        </div>
    </div>
</template>
