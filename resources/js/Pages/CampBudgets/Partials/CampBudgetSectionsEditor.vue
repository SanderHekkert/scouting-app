<script setup>
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    form: { type: Object, required: true },
    activeSectionIndex: { type: Number, required: true },
    addSection: { type: Function, required: true },
    removeSection: { type: Function, required: true },
    addRow: { type: Function, required: true },
    removeRow: { type: Function, required: true },
    isFixedQuantityFormulaRow: { type: Function, required: true },
    onRowQuantityInput: { type: Function, required: true },
    rowAmountInputValue: { type: Function, required: true },
    isAmountReadOnly: { type: Function, required: true },
    onRowAmountFocus: { type: Function, required: true },
    onRowAmountInput: { type: Function, required: true },
    onRowAmountBlur: { type: Function, required: true },
    rowComputedTotal: { type: Function, required: true },
    sectionTotal: { type: Function, required: true },
    formatMoney: { type: Function, required: true },
});

const emit = defineEmits(['update:active-section-index']);
</script>

<template>
    <div class="space-y-3 rounded-xl border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-app-canvas-dark">
        <div class="flex flex-wrap items-center gap-2">
            <button
                v-for="(section, idx) in props.form.budget_sections"
                :key="`section-tab-${idx}`"
                type="button"
                class="rounded-md border px-3 py-1.5 text-sm"
                :class="idx === props.activeSectionIndex ? 'border-brand-blue bg-brand-blue/10 text-app-ink dark:text-app-ink-dark' : 'border-app-border bg-white text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark'"
                @click="emit('update:active-section-index', idx)"
            >
                {{ section.title || `Sectie ${idx + 1}` }}
            </button>
            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-white" title="Sectie toevoegen" @click="props.addSection">
                <PlusIcon class="h-4 w-4" />
            </button>
        </div>

        <div v-if="props.form.budget_sections[props.activeSectionIndex]" class="space-y-3">
            <div class="flex items-center gap-2">
                <input
                    v-model="props.form.budget_sections[props.activeSectionIndex].title"
                    type="text"
                    class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-slate-900 dark:text-app-ink-dark"
                    placeholder="Naam sectie"
                />
                <button type="button" class="btn-action-delete" title="Sectie verwijderen" @click="props.removeSection(props.activeSectionIndex)">
                    <TrashIcon class="h-5 w-5" />
                </button>
            </div>

            <div class="overflow-x-auto rounded border border-app-border">
                <table class="w-full table-fixed text-sm">
                    <colgroup>
                        <col class="w-[42%]" />
                        <col class="w-24" />
                        <col class="w-36" />
                        <col class="w-36" />
                        <col class="w-16" />
                    </colgroup>
                    <thead class="bg-slate-50 text-app-ink dark:bg-slate-900 dark:text-app-ink-dark">
                        <tr>
                            <th class="px-1.5 py-2 text-left">Post</th>
                            <th class="px-1.5 py-2 text-left">Aantal</th>
                            <th class="px-1.5 py-2 text-left">Bedrag</th>
                            <th class="px-1.5 py-2 text-left">Totaal</th>
                            <th class="px-1.5 py-2 text-left">Actie</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-app-border bg-white dark:divide-app-border-dark dark:bg-app-canvas-dark">
                        <tr v-for="(row, rowIdx) in props.form.budget_sections[props.activeSectionIndex].rows" :key="`row-${rowIdx}`">
                            <td class="px-1.5 py-2">
                                <input v-model="row.label" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-slate-900 dark:text-app-ink-dark" />
                            </td>
                            <td class="px-1.5 py-2">
                                <input
                                    v-model.number="row.quantity"
                                    type="number"
                                    :min="props.isFixedQuantityFormulaRow(props.form.budget_sections[props.activeSectionIndex].title, row.label) ? 1 : 0"
                                    step="1"
                                    inputmode="numeric"
                                    :readonly="props.isFixedQuantityFormulaRow(props.form.budget_sections[props.activeSectionIndex].title, row.label)"
                                    class="w-24 rounded border border-app-border bg-white px-2 py-1.5 text-black dark:border-app-border-dark dark:bg-slate-900 dark:text-app-ink-dark readonly:bg-slate-100 readonly:text-slate-700 dark:readonly:bg-slate-800 dark:readonly:text-app-ink-dark"
                                    @input="props.onRowQuantityInput(row, props.form.budget_sections[props.activeSectionIndex].title)"
                                />
                            </td>
                            <td class="px-1.5 py-2">
                                <div class="relative w-36">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center rounded-l border-r border-app-border bg-slate-100 px-2 font-semibold text-slate-700 dark:border-app-border-dark dark:bg-slate-800 dark:text-app-ink-dark">€</span>
                                    <input :value="props.rowAmountInputValue(row, props.form.budget_sections[props.activeSectionIndex].title, props.activeSectionIndex, rowIdx)" type="text" inputmode="decimal" :readonly="props.isAmountReadOnly(props.form.budget_sections[props.activeSectionIndex].title, row.label)" class="w-full rounded border border-app-border bg-white py-1.5 pl-8 pr-2 text-black dark:border-app-border-dark dark:bg-slate-900 dark:text-app-ink-dark readonly:bg-slate-100 readonly:text-slate-700 dark:readonly:bg-slate-800 dark:readonly:text-app-ink-dark" @focus="props.onRowAmountFocus(row, props.activeSectionIndex, rowIdx)" @input="props.onRowAmountInput(row, $event, props.activeSectionIndex, rowIdx)" @blur="props.onRowAmountBlur(row, props.activeSectionIndex, rowIdx)" />
                                </div>
                            </td>
                            <td class="px-1.5 py-2 whitespace-nowrap">
                                <span class="text-base font-bold text-brand-blue-dark dark:text-brand-blue-light">€ {{ props.formatMoney(props.rowComputedTotal(row, props.form.budget_sections[props.activeSectionIndex].title)) }}</span>
                            </td>
                            <td class="px-1.5 py-2">
                                <button type="button" class="btn-action-delete" title="Regel verwijderen" @click="props.removeRow(props.activeSectionIndex, rowIdx)">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between">
                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-white" title="Regel toevoegen" @click="props.addRow(props.activeSectionIndex)">
                    <PlusIcon class="h-4 w-4" />
                </button>
                <p class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Sectietotaal: € {{ props.formatMoney(props.sectionTotal(props.form.budget_sections[props.activeSectionIndex])) }}</p>
            </div>
        </div>
    </div>
</template>
