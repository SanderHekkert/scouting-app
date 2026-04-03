<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message">
        <p class="text-sm font-medium text-brand-red dark:text-red-300">
            {{ message }}
        </p>
    </div>
</template>
