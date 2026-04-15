<script setup>
import { MoonIcon, SunIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    themeTrackDark: { type: Boolean, default: false },
    themeKnobStyle: { type: Object, required: true },
    themeBusy: { type: Boolean, default: false },
    themeKnobIcon: { type: [Object, Function], required: true },
    themeKnobIconStyle: { type: Object, required: true },
    onThemeKnobMouseDown: { type: Function, required: true },
    onThemeKnobTouchStart: { type: Function, required: true },
    toggleTheme: { type: Function, required: true },
});
</script>

<template>
    <div class="surface-brand-top rounded-2xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-app-border-dark dark:bg-app-panel-dark sm:p-6">
        <div class="w-full">
            <h3 class="text-lg font-medium text-app-ink dark:text-app-ink-dark">
                Weergave
            </h3>
            <p class="mt-1 text-sm text-app-muted dark:text-app-muted-dark">
                Schakel tussen lichte en donkere modus.
            </p>
            <div class="mt-4 flex items-center justify-between rounded-xl border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-app-canvas-dark">
                <div class="flex items-center gap-2 text-sm font-medium text-app-ink dark:text-app-ink-dark">
                    <SunIcon class="h-5 w-5 text-amber-500" />
                    <span>Licht</span>
                </div>
                <div
                    class="relative inline-flex h-10 w-52 items-center rounded-xl transition"
                    :class="props.themeTrackDark ? 'bg-brand-blue' : 'bg-slate-300 dark:bg-slate-600'"
                >
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 cursor-grab items-center justify-center rounded-full bg-white text-slate-700 shadow transition-transform active:cursor-grabbing focus:outline-none focus:ring-2 focus:ring-brand-blue/40 disabled:opacity-60"
                        :style="props.themeKnobStyle"
                        :disabled="props.themeBusy"
                        title="Sleep om te wisselen"
                        aria-label="Sleep om te wisselen"
                        @mousedown.prevent="props.onThemeKnobMouseDown"
                        @touchstart.prevent="props.onThemeKnobTouchStart"
                        @keydown.enter.prevent="props.toggleTheme"
                        @keydown.space.prevent="props.toggleTheme"
                    >
                        <component :is="props.themeKnobIcon" class="h-4 w-4 transition-transform" :style="props.themeKnobIconStyle" />
                    </button>
                </div>
                <div class="flex items-center gap-2 text-sm font-medium text-app-ink dark:text-app-ink-dark">
                    <span>Donker</span>
                    <MoonIcon class="h-5 w-5 text-indigo-500" />
                </div>
            </div>
        </div>
    </div>
</template>
