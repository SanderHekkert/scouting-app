<script setup>
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    agreementItems: { type: Array, required: true },
    hygieneRows: { type: Array, required: true },
    addSpeltakAgreementItem: { type: Function, required: true },
    removeSpeltakAgreementItem: { type: Function, required: true },
    addSpeltakAgreementBullet: { type: Function, required: true },
    removeSpeltakAgreementBullet: { type: Function, required: true },
    addSpeltakHygieneRow: { type: Function, required: true },
    removeSpeltakHygieneRow: { type: Function, required: true },
});
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <button type="button" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800" @click="props.addSpeltakAgreementItem">
                Blok toevoegen
            </button>
        </div>

        <div
            v-for="(item, itemIdx) in props.agreementItems"
            :key="`speltak-agreement-item-${itemIdx}`"
            class="rounded-lg border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-slate-900"
        >
            <div class="flex items-center justify-between gap-2">
                <input
                    v-model="item.title"
                    type="text"
                    class="w-full rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    :placeholder="`Kop ${itemIdx + 1}`"
                />
                <button type="button" class="btn-action-delete" title="Blok verwijderen" @click="props.removeSpeltakAgreementItem(itemIdx)">
                    <TrashIcon class="h-5 w-5" />
                </button>
            </div>

            <div class="mt-3 space-y-2">
                <div
                    v-for="(bullet, bulletIdx) in item.bullets"
                    :key="`speltak-agreement-bullet-${itemIdx}-${bulletIdx}`"
                    class="flex items-center gap-2"
                >
                    <span class="text-sm text-app-muted dark:text-app-muted-dark">•</span>
                    <input
                        v-model="item.bullets[bulletIdx]"
                        type="text"
                        class="w-full rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                        placeholder="Bulletpoint"
                    />
                    <button type="button" class="btn-action-delete" title="Bulletpoint verwijderen" @click="props.removeSpeltakAgreementBullet(itemIdx, bulletIdx)">
                        <TrashIcon class="h-5 w-5" />
                    </button>
                </div>
                <button type="button" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800" @click="props.addSpeltakAgreementBullet(itemIdx)">
                    Bulletpoint toevoegen
                </button>
            </div>
        </div>

        <div class="space-y-2 rounded-lg border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h5 class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Hygiëne en gezondheid tabel</h5>
            </div>
            <div class="overflow-x-auto rounded border border-app-border dark:border-app-border-dark">
                <table class="w-full min-w-[980px] text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/70">
                        <tr>
                            <th class="px-2 py-2 text-left">Onderwerp</th>
                            <th class="px-2 py-2 text-left">Jerrycans</th>
                            <th class="px-2 py-2 text-left">Kraanwater</th>
                            <th class="px-2 py-2 text-left">Buitenboordwater</th>
                            <th class="px-2 py-2 text-left">Desinfectans</th>
                            <th class="px-2 py-2 text-left">Actie</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-app-border dark:divide-app-border-dark">
                        <tr v-for="(row, rowIdx) in props.hygieneRows" :key="`speltak-hygiene-row-${rowIdx}`">
                            <td class="px-2 py-2"><input v-model="row.topic" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Onderwerp" /></td>
                            <td class="px-2 py-2"><input v-model="row.jerrycans" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Ja/Nee" /></td>
                            <td class="px-2 py-2"><input v-model="row.kraanwater" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Ja/Nee" /></td>
                            <td class="px-2 py-2"><input v-model="row.buitenboordwater" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Ja/Nee" /></td>
                            <td class="px-2 py-2"><input v-model="row.desinfectans" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Ja/Nee" /></td>
                            <td class="px-2 py-2">
                                <button type="button" class="btn-action-delete" title="Rij verwijderen" @click="props.removeSpeltakHygieneRow(rowIdx)">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div>
                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-white" title="Rij toevoegen" aria-label="Rij toevoegen" @click="props.addSpeltakHygieneRow">
                    <PlusIcon class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</template>
