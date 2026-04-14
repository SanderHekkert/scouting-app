<script setup>
import Modal from '@/Components/Modal.vue';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Weet je het zeker?' },
    message: { type: String, default: '' },
    confirmText: { type: String, default: 'Verwijderen' },
    cancelText: { type: String, default: 'Annuleren' },
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="rainbow-animate h-1 w-full bg-gradient-to-r from-brand-red via-brand-yellow to-brand-blue" aria-hidden="true" />
        <div class="p-6 sm:p-7">
            <div class="flex flex-col items-center gap-3 text-center">
                <span
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-red/10 text-brand-red dark:bg-brand-red/20"
                    aria-hidden="true"
                >
                    <ExclamationTriangleIcon class="h-5 w-5" />
                </span>
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">
                        {{ title }}
                    </h3>
                    <p class="mt-1 text-sm text-app-muted dark:text-app-muted-dark">
                        {{ message }}
                    </p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-end gap-2">
                <button
                    type="button"
                    class="inline-flex min-h-10 items-center rounded-md border border-app-border bg-app-panel px-4 py-2 text-sm font-medium text-app-ink transition hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15"
                    @click="emit('close')"
                >
                    {{ cancelText }}
                </button>
                <button
                    type="button"
                    class="inline-flex min-h-10 items-center rounded-md border border-brand-red/70 bg-brand-red px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-red-dark"
                    @click="emit('confirm')"
                >
                    {{ confirmText }}
                </button>
            </div>
        </div>
    </Modal>
</template>
