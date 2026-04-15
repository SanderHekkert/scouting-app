<script setup>
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    form: { type: Object, required: true },
    isFramCamp: { type: Boolean, default: false },
    speltakLabel: { type: String, required: true },
    addMonsterrolRow: { type: Function, required: true },
    removeMonsterrolRow: { type: Function, required: true },
});
</script>

<template>
    <div class="space-y-4">
        <div class="space-y-2 rounded-lg border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Staf en vaarbemanning</h4>
            </div>
            <div class="overflow-x-auto rounded border border-app-border dark:border-app-border-dark">
                <table class="w-full min-w-[920px] text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/70">
                        <tr>
                            <th class="px-2 py-2 text-left">Voornaam</th>
                            <th class="px-2 py-2 text-left">Achternaam</th>
                            <th class="px-2 py-2 text-left">Functie</th>
                            <th v-if="props.isFramCamp" class="px-2 py-2 text-left">Aan boord</th>
                            <th v-if="props.isFramCamp" class="px-2 py-2 text-left">Van boord</th>
                            <th class="px-2 py-2 text-left">Actie</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-app-border dark:divide-app-border-dark">
                        <tr v-for="(row, rowIdx) in props.form.monsterrol_rows.crew" :key="`monsterrol-crew-${rowIdx}`">
                            <td class="px-2 py-2"><input v-model="row.first_name" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Voornaam" /></td>
                            <td class="px-2 py-2"><input v-model="row.last_name" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Achternaam" /></td>
                            <td class="px-2 py-2"><input v-model="row.functie" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Functie" /></td>
                            <td v-if="props.isFramCamp" class="px-2 py-2"><input v-model="row.on_board" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Aan boord" /></td>
                            <td v-if="props.isFramCamp" class="px-2 py-2"><input v-model="row.off_board" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Van boord" /></td>
                            <td class="px-2 py-2">
                                <button type="button" class="btn-action-delete" title="Rij verwijderen" @click="props.removeMonsterrolRow('crew', rowIdx)">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div>
                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-white" title="Rij toevoegen" aria-label="Rij toevoegen" @click="props.addMonsterrolRow('crew')">
                    <PlusIcon class="h-4 w-4" />
                </button>
            </div>
        </div>

        <div class="space-y-2 rounded-lg border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h4 class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">{{ props.speltakLabel }}</h4>
                </div>
            </div>
            <div class="overflow-x-auto rounded border border-app-border dark:border-app-border-dark">
                <table class="w-full min-w-[920px] text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/70">
                        <tr>
                            <th class="px-2 py-2 text-left">Voornaam</th>
                            <th class="px-2 py-2 text-left">Achternaam</th>
                            <th class="px-2 py-2 text-left">Functie</th>
                            <th v-if="props.isFramCamp" class="px-2 py-2 text-left">Aan boord</th>
                            <th v-if="props.isFramCamp" class="px-2 py-2 text-left">Van boord</th>
                            <th class="px-2 py-2 text-left">Actie</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-app-border dark:divide-app-border-dark">
                        <tr v-for="(row, rowIdx) in props.form.monsterrol_rows.speltak" :key="`monsterrol-speltak-${rowIdx}`">
                            <td class="px-2 py-2"><input v-model="row.first_name" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Voornaam" /></td>
                            <td class="px-2 py-2"><input v-model="row.last_name" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Achternaam" /></td>
                            <td class="px-2 py-2"><input v-model="row.functie" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Functie" /></td>
                            <td v-if="props.isFramCamp" class="px-2 py-2"><input v-model="row.on_board" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Aan boord" /></td>
                            <td v-if="props.isFramCamp" class="px-2 py-2"><input v-model="row.off_board" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Van boord" /></td>
                            <td class="px-2 py-2">
                                <button type="button" class="btn-action-delete" title="Rij verwijderen" @click="props.removeMonsterrolRow('speltak', rowIdx)">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div>
                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-white" title="Rij toevoegen" aria-label="Rij toevoegen" @click="props.addMonsterrolRow('speltak')">
                    <PlusIcon class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</template>
