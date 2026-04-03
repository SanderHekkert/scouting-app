<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2 text-start text-sm leading-5 text-app-ink transition duration-150 ease-in-out hover:bg-brand-blue/10 focus:bg-brand-blue/10 focus:outline-none dark:text-app-ink-dark dark:hover:bg-brand-blue/20 dark:focus:bg-brand-blue/20"
    >
        <slot />
    </Link>
</template>
