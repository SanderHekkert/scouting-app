<script setup>
import { TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    items: { type: Array, required: true },
    addTaskExplanationItem: { type: Function, required: true },
    removeTaskExplanationItem: { type: Function, required: true },
    addTaskBullet: { type: Function, required: true },
    removeTaskBullet: { type: Function, required: true },
});
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <button type="button" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800" @click="props.addTaskExplanationItem">
                Taak toevoegen
            </button>
        </div>

        <div
            v-for="(item, itemIdx) in props.items"
            :key="`task-explanation-item-${itemIdx}`"
            class="rounded-lg border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-slate-900"
        >
            <div class="flex items-center justify-between gap-2">
                <input
                    v-model="item.title"
                    type="text"
                    class="w-full rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    :placeholder="`Taak ${itemIdx + 1}`"
                />
                <button type="button" class="btn-action-delete" title="Taak verwijderen" @click="props.removeTaskExplanationItem(itemIdx)">
                    <TrashIcon class="h-5 w-5" />
                </button>
            </div>

            <div class="mt-3 space-y-2">
                <div
                    v-for="(bullet, bulletIdx) in item.bullets"
                    :key="`task-bullet-${itemIdx}-${bulletIdx}`"
                    class="flex items-center gap-2"
                >
                    <span class="text-sm text-app-muted dark:text-app-muted-dark">•</span>
                    <input
                        v-model="item.bullets[bulletIdx]"
                        type="text"
                        class="w-full rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                        placeholder="Bulletpoint"
                    />
                    <button type="button" class="btn-action-delete" title="Bulletpoint verwijderen" @click="props.removeTaskBullet(itemIdx, bulletIdx)">
                        <TrashIcon class="h-5 w-5" />
                    </button>
                </div>
                <button type="button" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800" @click="props.addTaskBullet(itemIdx)">
                    Bulletpoint toevoegen
                </button>
            </div>
        </div>
    </div>
</template>
