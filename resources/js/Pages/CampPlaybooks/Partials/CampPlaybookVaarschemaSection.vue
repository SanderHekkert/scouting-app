<script setup>
const props = defineProps({
    vaarschemaRows: { type: Array, required: true },
    normalizeWholeMinutes: { type: Function, required: true },
});

const emit = defineEmits(['add-row', 'remove-row']);
</script>

<template>
    <div class="space-y-3">
        <div class="rounded-lg border border-brand-blue/25 bg-brand-blue/5 p-3 text-sm text-app-ink dark:border-brand-blue/40 dark:bg-brand-blue/10 dark:text-app-ink-dark">
            <p class="font-semibold">Website getij</p>
            <a
                href="https://waterinfo.rws.nl/publiek/astronomische-getij/heinenoord.goidschalxoord/details"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-1 inline-block break-all text-brand-blue underline"
            >
                https://waterinfo.rws.nl/publiek/astronomische-getij/heinenoord.goidschalxoord/details
            </a>
            <p class="mt-2 text-xs">
                Note: We kunnen met 60 NAP net wel naar binnen in de Koedood. Voor de veiligheid 75 NAP aanhouden.
            </p>
        </div>

        <div class="overflow-x-auto rounded border border-app-border dark:border-app-border-dark">
            <table class="w-full min-w-[880px] text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/70">
                    <tr>
                        <th class="px-2 py-2 text-left">Datum</th>
                        <th class="px-2 py-2 text-left">Van</th>
                        <th class="px-2 py-2 text-left">Naar</th>
                        <th class="px-2 py-2 text-left">Wegvaren</th>
                        <th class="px-2 py-2 text-left">Aankomen</th>
                        <th class="px-2 py-2 text-left">Speling (minuten)</th>
                        <th class="px-2 py-2 text-left">Actie</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-app-border dark:divide-app-border-dark">
                    <tr v-for="(row, rowIdx) in props.vaarschemaRows" :key="`vaarschema-row-${rowIdx}`">
                        <td class="px-2 py-2"><input v-model="row.date" type="date" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" /></td>
                        <td class="px-2 py-2"><input v-model="row.from" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Van" /></td>
                        <td class="px-2 py-2"><input v-model="row.to" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Naar" /></td>
                        <td class="px-2 py-2"><input v-model="row.depart_at" type="time" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" /></td>
                        <td class="px-2 py-2"><input v-model="row.arrive_at" type="time" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" /></td>
                        <td class="px-2 py-2"><input v-model="row.tide_margin_minutes" type="number" min="0" step="1" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="0" @input="row.tide_margin_minutes = props.normalizeWholeMinutes(row.tide_margin_minutes)" /></td>
                        <td class="px-2 py-2">
                            <button type="button" class="btn-action-delete" title="Rij verwijderen" @click="emit('remove-row', rowIdx)">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.087-2.201a51.964 51.964 0 0 0-3.326 0c-1.176.037-2.087 1.022-2.087 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div>
            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-white" title="Rij toevoegen" aria-label="Rij toevoegen" @click="emit('add-row')">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </button>
        </div>
    </div>
</template>
