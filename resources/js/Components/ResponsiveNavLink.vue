<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-brand-yellow dark:border-brand-blue text-start text-base font-medium text-brand-red dark:text-app-ink-dark bg-brand-red/10 dark:bg-brand-blue/25 focus:outline-none focus:text-brand-red-dark dark:focus:text-app-ink-dark focus:bg-brand-red/10 dark:focus:bg-brand-blue/35 focus:border-brand-red dark:focus:border-brand-yellow-soft transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-app-muted dark:text-app-muted-dark hover:text-brand-blue dark:hover:text-brand-blue-light hover:bg-brand-blue/8 dark:hover:bg-brand-blue/15 hover:border-brand-blue/30 dark:hover:border-brand-blue/45 focus:outline-none focus:text-brand-blue-dark dark:focus:text-app-ink-dark focus:bg-brand-blue/10 dark:focus:bg-brand-blue/20 focus:border-brand-blue/40 dark:focus:border-brand-yellow/50 transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
