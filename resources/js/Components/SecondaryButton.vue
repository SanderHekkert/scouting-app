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
        class="inline-flex items-center rounded-md border-2 border-gray-900 bg-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest !text-neutral-900 shadow-sm transition duration-150 ease-in-out hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 dark:border-gray-900 dark:bg-gray-400 dark:!text-neutral-900 dark:hover:bg-gray-500 dark:focus:ring-offset-gray-800"
    >
        <slot />
    </button>
</template>
