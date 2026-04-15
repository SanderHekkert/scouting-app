<script setup>
import { PlusIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    dayPlans: { type: Array, required: true },
    leaderTeam: { type: Array, default: () => [] },
    removePlanningDay: { type: Function, required: true },
    normalizedDaywatchIds: { type: Function, required: true },
    daywatchNameById: { type: Function, required: true },
    removeDaywatch: { type: Function, required: true },
    addDaywatchFromSelect: { type: Function, required: true },
    availableDaywatchOptions: { type: Function, required: true },
    startPlanningRowDrag: { type: Function, required: true },
    allowPlanningRowDrop: { type: Function, required: true },
    dropPlanningRow: { type: Function, required: true },
    endPlanningRowDrag: { type: Function, required: true },
    removePlanningRow: { type: Function, required: true },
    addPlanningRow: { type: Function, required: true },
    autosizeTextarea: { type: Function, required: true },
    addPlanningDay: { type: Function, required: true },
});
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-center">
            <h4 class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Dagen</h4>
        </div>

        <div
            v-for="(day, dayIdx) in props.dayPlans"
            :key="`planning-day-${dayIdx}`"
            class="rounded-lg border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-slate-900"
        >
            <div class="flex items-center justify-between gap-2">
                <input
                    v-model="day.day_label"
                    type="text"
                    class="w-full rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    placeholder="Bijv. Dag 1 - Vrijdag"
                />
                <button type="button" class="btn-action-delete" title="Dag verwijderen" @click="props.removePlanningDay(dayIdx)">
                    <TrashIcon class="h-5 w-5" />
                </button>
            </div>

            <div class="mt-3 space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Dagwacht (leidingteam)</p>
                <div class="space-y-2">
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="leaderId in props.normalizedDaywatchIds(day)"
                            :key="`daywatch-chip-${dayIdx}-${leaderId}`"
                            class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs text-app-ink dark:text-app-ink-dark"
                        >
                            {{ props.daywatchNameById(leaderId) }}
                            <button type="button" class="rounded p-0.5 hover:bg-brand-blue/25" @click="props.removeDaywatch(day, leaderId)">
                                <XMarkIcon class="h-3.5 w-3.5" />
                            </button>
                        </span>
                    </div>
                    <select class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" @change="props.addDaywatchFromSelect(day, $event)">
                        <option value="">Dagwacht toevoegen...</option>
                        <option v-for="leader in props.availableDaywatchOptions(day)" :key="`daywatch-option-${dayIdx}-${leader.id}`" :value="leader.id">
                            {{ leader.name }}
                        </option>
                    </select>
                    <span v-if="!props.leaderTeam.length" class="text-xs text-app-muted dark:text-app-muted-dark">Geen leidingteam gevonden in deze speltak.</span>
                </div>
            </div>

            <div class="mt-3 overflow-x-auto rounded border border-app-border dark:border-app-border-dark">
                <table class="w-full min-w-[680px] text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/70">
                        <tr>
                            <th class="px-2 py-2 text-left">Tijden</th>
                            <th class="px-2 py-2 text-left">Programma</th>
                            <th class="px-2 py-2 text-left">Spel</th>
                            <th class="px-2 py-2 text-left">Benodigdheden</th>
                            <th class="px-2 py-2 text-left">Actie</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-app-border dark:divide-app-border-dark">
                        <tr
                            v-for="(row, rowIdx) in day.planning_rows"
                            :key="`planning-row-${dayIdx}-${rowIdx}`"
                            draggable="true"
                            class="cursor-move"
                            @dragstart="props.startPlanningRowDrag(dayIdx, rowIdx, $event)"
                            @dragover="props.allowPlanningRowDrop($event)"
                            @drop="props.dropPlanningRow(dayIdx, rowIdx, $event)"
                            @dragend="props.endPlanningRowDrag"
                        >
                            <td class="px-2 py-2"><input v-model="row.time" type="time" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" /></td>
                            <td class="px-2 py-2"><input v-model="row.program" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Programma" /></td>
                            <td class="px-2 py-2"><input v-model="row.game" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Spel" /></td>
                            <td class="px-2 py-2"><input v-model="row.needs" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Benodigdheden" /></td>
                            <td class="px-2 py-2">
                                <button type="button" class="btn-action-delete" title="Rij verwijderen" @click="props.removePlanningRow(dayIdx, rowIdx)">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-2">
                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-white" title="Rij toevoegen" aria-label="Rij toevoegen" @click="props.addPlanningRow(dayIdx)">
                    <PlusIcon class="h-4 w-4" />
                </button>
            </div>

            <div class="mt-3 space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Speluitleg</label>
                <textarea v-model="day.game_explanation" rows="4" data-speluitleg-autoresize="true" class="w-full resize-none overflow-hidden rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Leg spelregels, doelen en aandachtspunten uit..." @input="props.autosizeTextarea($event.target)" />
            </div>
        </div>

        <div>
            <button type="button" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800" @click="props.addPlanningDay">
                Dag toevoegen
            </button>
        </div>
    </div>
</template>
