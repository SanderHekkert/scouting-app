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
        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-brand-yellow dark:border-brand-blue text-sm font-medium leading-5 text-brand-blue-dark dark:text-brand-yellow-soft focus:outline-none focus:border-brand-red transition duration-150 ease-in-out'
        : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-app-muted dark:text-app-muted-dark hover:text-brand-blue dark:hover:text-brand-blue-light hover:border-brand-blue/35 dark:hover:border-brand-blue/50 focus:outline-none focus:text-brand-blue-dark dark:focus:text-brand-yellow-soft focus:border-brand-blue/40 dark:focus:border-brand-yellow/50 transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
