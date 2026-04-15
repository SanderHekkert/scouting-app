<script setup>
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    rows: { type: Array, required: true },
    normalizeResponsibleNames: { type: Function, required: true },
    isDagverloopTask: { type: Function, required: true },
    removeResponsibleFromTask: { type: Function, required: true },
    addResponsibleToTask: { type: Function, required: true },
    availableResponsibleOptions: { type: Function, required: true },
});
</script>

<template>
    <div class="space-y-3">
        <div class="overflow-x-auto rounded border border-app-border dark:border-app-border-dark">
            <table class="w-full min-w-[840px] text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/70">
                    <tr>
                        <th class="px-2 py-2 text-left">Taak</th>
                        <th class="px-2 py-2 text-left">Verantwoordelijke</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-app-border dark:divide-app-border-dark">
                    <tr v-for="(row, rowIdx) in props.rows" :key="`task-distribution-row-${rowIdx}`">
                        <td class="px-2 py-2"><input v-model="row.task" type="text" readonly class="w-full rounded border border-app-border bg-slate-100 px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-slate-800 dark:text-app-ink-dark" placeholder="Taak" /></td>
                        <td class="px-2 py-2">
                            <div class="space-y-2">
                                <div class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="name in props.normalizeResponsibleNames(row.responsibles)"
                                        :key="`task-responsible-chip-${rowIdx}-${name}`"
                                        class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs text-app-ink dark:text-app-ink-dark"
                                    >
                                        {{ name }}
                                        <button v-if="!props.isDagverloopTask(row.task)" type="button" class="rounded p-0.5 hover:bg-brand-blue/25" @click="props.removeResponsibleFromTask(row, name)">
                                            <XMarkIcon class="h-3.5 w-3.5" />
                                        </button>
                                    </span>
                                </div>
                                <select class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black disabled:bg-slate-100 disabled:text-slate-500 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:disabled:bg-slate-800 dark:disabled:text-slate-400" :disabled="props.isDagverloopTask(row.task)" @change="props.addResponsibleToTask(row, $event)">
                                    <option value="">Verantwoordelijke toevoegen...</option>
                                    <option v-for="name in props.availableResponsibleOptions(row)" :key="`responsible-option-${rowIdx}-${name}`" :value="name">
                                        {{ name }}
                                    </option>
                                </select>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
