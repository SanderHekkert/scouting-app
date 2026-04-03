<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message">
        <p class="text-sm font-medium text-brand-red dark:text-brand-yellow-soft">
            {{ message }}
        </p>
    </div>
</template>
