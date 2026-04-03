<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        class="rounded border-app-border text-brand-red shadow-sm focus:ring-2 focus:ring-brand-blue/40 dark:border-app-border-dark dark:bg-app-canvas-dark dark:focus:ring-2 dark:focus:ring-brand-red/50 dark:focus:ring-offset-app-canvas-dark"
    />
</template>
