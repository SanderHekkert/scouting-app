<script setup>
import { BellAlertIcon, BellSlashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    pushSupportIssue: { type: String, default: '' },
    pushTrackEnabled: { type: Boolean, default: false },
    pushKnobStyle: { type: Object, required: true },
    pushBusy: { type: Boolean, default: false },
    pushEnabled: { type: Boolean, default: false },
    pushKnobIcon: { type: [Object, Function], required: true },
    pushKnobIconStyle: { type: Object, required: true },
    pushMessage: { type: String, default: '' },
    pushError: { type: String, default: '' },
    onPushKnobMouseDown: { type: Function, required: true },
    onPushKnobTouchStart: { type: Function, required: true },
    setPushEnabled: { type: Function, required: true },
});
</script>

<template>
    <div class="surface-brand-top rounded-2xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-app-border-dark dark:bg-app-panel-dark sm:p-6">
        <div class="w-full">
            <h3 class="text-lg font-medium text-app-ink dark:text-app-ink-dark">
                Pushmeldingen
            </h3>
            <p class="mt-1 text-sm text-app-muted dark:text-app-muted-dark">
                Meldingen voor dit apparaat.
            </p>
            <p v-if="props.pushSupportIssue" class="mt-2 text-sm text-amber-700 dark:text-amber-300">
                {{ props.pushSupportIssue }}
            </p>

            <div class="mt-4 flex items-center justify-between rounded-xl border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-app-canvas-dark">
                <div class="flex items-center gap-2 text-sm font-medium text-app-ink dark:text-app-ink-dark">
                    <BellSlashIcon class="h-5 w-5 text-slate-500 dark:text-slate-300" />
                    <span>Uit</span>
                </div>
                <div
                    class="relative inline-flex h-10 w-52 items-center rounded-xl transition"
                    :class="props.pushTrackEnabled ? 'bg-emerald-600' : 'bg-slate-300 dark:bg-slate-600'"
                >
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 cursor-grab items-center justify-center rounded-full bg-white text-slate-700 shadow transition-transform active:cursor-grabbing focus:outline-none focus:ring-2 focus:ring-emerald-500/40 disabled:opacity-60"
                        :style="props.pushKnobStyle"
                        :disabled="props.pushBusy || !!props.pushSupportIssue"
                        title="Sleep om push te wisselen"
                        aria-label="Sleep om push te wisselen"
                        @mousedown.prevent="props.onPushKnobMouseDown"
                        @touchstart.prevent="props.onPushKnobTouchStart"
                        @keydown.enter.prevent="props.setPushEnabled(!props.pushEnabled)"
                        @keydown.space.prevent="props.setPushEnabled(!props.pushEnabled)"
                    >
                        <component :is="props.pushKnobIcon" class="h-4 w-4 transition-transform" :style="props.pushKnobIconStyle" />
                    </button>
                </div>
                <div class="flex items-center gap-2 text-sm font-medium text-app-ink dark:text-app-ink-dark">
                    <span>Aan</span>
                    <BellAlertIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-300" />
                </div>
            </div>

            <p v-if="props.pushMessage" class="mt-3 text-sm text-emerald-700 dark:text-emerald-300">{{ props.pushMessage }}</p>
            <p v-if="props.pushError" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ props.pushError }}</p>
        </div>
    </div>
</template>
