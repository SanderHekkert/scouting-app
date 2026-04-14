<script setup>
import { computed, ref } from 'vue';
import { formatMoney, sanitizeMoneyInput } from '@/utils/money';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, ArrowUturnLeftIcon, DocumentArrowDownIcon, DocumentCheckIcon, DocumentDuplicateIcon, PaperAirplaneIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    mode: { type: String, default: 'create' },
    item: { type: Object, default: null },
    copyItem: { type: Object, default: null },
    defaultSections: { type: Array, default: () => [] },
    defaultStandardValues: { type: Object, default: () => ({}) },
});

const page = usePage();
const perms = computed(() => page.props.auth?.permissions?.camp_budgets ?? {});
const canCreate = computed(() => !!perms.value.create);
const canUpdate = computed(() => !!perms.value.update);
const isEdit = computed(() => props.mode === 'edit' && !!props.item?.id);

const sectionLabels = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const speltakLabel = computed(() => sectionLabels[page.props.auth?.active_section] || 'Dolfijnen');

const source = props.item || props.copyItem || {};
const currentStatus = computed(() => String(source.status || 'draft'));
const initialSections = source.budget_sections || props.defaultSections || [];
const initialStandardValues = source.standard_values || props.defaultStandardValues || {};
const form = useForm({
    camp_year: source.camp_year || new Date().getFullYear(),
    camp_days: Number(source.camp_days ?? 1),
    title: source.title || '',
    content: source.content || '',
    camp_location: source.camp_location === 'clubhuis' ? 'clubhuis' : 'fram',
    budget_sections: JSON.parse(JSON.stringify(initialSections)),
    standard_values: {
        prijs_per_dag_clubhuis: Number(initialStandardValues.prijs_per_dag_clubhuis ?? 0),
        prijs_per_dag_leiding: Number(initialStandardValues.prijs_per_dag_leiding ?? 0),
        prijs_per_dag_jeugdlid: Number(initialStandardValues.prijs_per_dag_jeugdlid ?? 0),
        kosten_vaart_pu: Number(initialStandardValues.kosten_vaart_pu ?? 0),
        kosten_aggregaat_pu: Number(initialStandardValues.kosten_aggregaat_pu ?? 0),
        huur_fram_pppd: Number(initialStandardValues.huur_fram_pppd ?? 0),
        proviand_pppd: Number(initialStandardValues.proviand_pppd ?? 0),
        groepsafdracht_pjpd: Number(initialStandardValues.groepsafdracht_pjpd ?? 0),
        reservering_nawaka_pjpd: Number(initialStandardValues.reservering_nawaka_pjpd ?? 0),
    },
});
const campLocation = computed(() => (form.camp_location === 'clubhuis' ? 'clubhuis' : 'fram'));
const activeSectionIndex = ref(0);
const showDeleteModal = ref(false);

function normalizedCampDays(value) {
    const parsed = Number.parseInt(String(value ?? '').replace(/[^\d-]/g, ''), 10);
    if (Number.isNaN(parsed) || parsed < 1) return 1;
    return Math.min(parsed, 60);
}

function statusLabel(status) {
    if (status === 'submitted') return 'Wacht op goedkeuring';
    if (status === 'approved') return 'Goedgekeurd';
    if (status === 'needs_changes') return 'Aanpassen nodig';
    return 'Concept';
}

function statusClass(status) {
    if (status === 'approved') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300';
    if (status === 'submitted') return 'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-300';
    if (status === 'needs_changes') return 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300';
    return 'bg-slate-100 text-slate-700 dark:bg-slate-700/40 dark:text-slate-200';
}

function submit(action = 'save') {
    const normalizedAction = typeof action === 'string' && action.length > 0 ? action : 'save';
    const options = {
        preserveScroll: true,
        onFinish: () => form.transform((data) => data),
    };

    if (isEdit.value) {
        form
            .transform((data) => ({ ...data, action: normalizedAction }))
            .patch(route('camp-budgets.update', props.item.id), options);
        return;
    }
    form
        .transform((data) => ({ ...data, action: normalizedAction }))
        .post(route('camp-budgets.store'), options);
}

function submitForReview() {
    submit('submit');
}

function destroyItem() {
    if (!isEdit.value || !canUpdate.value) return;
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    showDeleteModal.value = false;
}

function confirmDeleteItem() {
    if (!isEdit.value || !props.item?.id) return;
    router.delete(route('camp-budgets.destroy', props.item.id));
    closeDeleteModal();
}

function copyItem() {
    if (!isEdit.value || !canCreate.value) return;
    router.post(route('camp-budgets.copy', props.item.id));
}

function addSection() {
    form.budget_sections.push({ title: 'Nieuwe sectie', rows: [] });
    activeSectionIndex.value = form.budget_sections.length - 1;
}

function removeSection(index) {
    if (form.budget_sections.length <= 1) return;
    form.budget_sections.splice(index, 1);
    if (activeSectionIndex.value >= form.budget_sections.length) {
        activeSectionIndex.value = form.budget_sections.length - 1;
    }
}

function addRow(sectionIndex) {
    form.budget_sections[sectionIndex].rows.push({ label: '', quantity: 0, amount: 0, note: '' });
}

function upsertExpenseRow(label) {
    const expensesSection = (form.budget_sections || []).find((section) => String(section?.title || '').trim().toLowerCase() === 'uitgaven');
    if (!expensesSection) return;
    const existing = (expensesSection.rows || []).some((row) => String(row?.label || '').trim().toLowerCase() === label.toLowerCase());
    if (existing) return;
    expensesSection.rows.push({ label, quantity: 0, amount: 0, note: '' });
}

function ensureExpenseRowsForLocation() {
    if (campLocation.value === 'fram') {
        upsertExpenseRow('Geschatte vaaruren');
        upsertExpenseRow('Geschatte aggregaaturen');
        return;
    }

    upsertExpenseRow('Kosten uitje');
    upsertExpenseRow('Clubhuis');
}

function normalizeWholeNumber(value) {
    const parsed = Number.parseInt(String(value ?? '').replace(/[^\d-]/g, ''), 10);
    if (Number.isNaN(parsed)) return 0;
    return Math.max(0, parsed);
}

function removeRow(sectionIndex, rowIndex) {
    form.budget_sections[sectionIndex].rows.splice(rowIndex, 1);
}

function sectionTotal(section) {
    return (section?.rows || []).reduce((sum, row) => {
        const quantity = normalizeWholeNumber(row.quantity);
        const amount = effectiveAmount(row, section?.title);
        return sum + (quantity * amount);
    }, 0);
}

function effectiveAmount(row, sectionTitle) {
    const label = String(row?.label || '').trim().toLowerCase();
    const section = String(sectionTitle || '').trim().toLowerCase();
    const manualAmount = Number(row?.amount || 0) || 0;
    if (manualAmount > 0) return manualAmount;
    if (!label) return manualAmount;
    const days = normalizedCampDays(form.camp_days);

    if (section === 'bijdragen' && label.includes('leiding')) return (Number(form.standard_values.prijs_per_dag_leiding) || 0) * days;
    if (label.includes('clubhuis')) return (Number(form.standard_values.prijs_per_dag_clubhuis) || 0) * days;
    if (label.includes('vaart')) return Number(form.standard_values.kosten_vaart_pu) || 0;
    if (label.includes('aggregaat')) return Number(form.standard_values.kosten_aggregaat_pu) || 0;
    if (label.includes('fram')) {
        if (campLocation.value === 'clubhuis') return (Number(form.standard_values.prijs_per_dag_clubhuis) || 0) * days;
        return (Number(form.standard_values.huur_fram_pppd) || 0) * days;
    }
    if (label.includes('proviand')) return (Number(form.standard_values.proviand_pppd) || 0) * days;
    if (label.includes('groepsafdracht')) return (Number(form.standard_values.groepsafdracht_pjpd) || 0) * days;
    if (label.includes('nawaka')) return (Number(form.standard_values.reservering_nawaka_pjpd) || 0) * days;
    return manualAmount;
}

function onStandardMoneyInput(field, event) {
    form.standard_values[field] = sanitizeMoneyInput(event?.target?.value ?? form.standard_values[field], { allowEmpty: false });
}

function onRowAmountInput(row, event) {
    row.amount = sanitizeMoneyInput(event?.target?.value ?? row.amount);
}

function moneyInputPreview(value, { fallback = '' } = {}) {
    if (value === null || value === undefined || value === '') return fallback;
    return String(value).replace('.', ',');
}

function setCampLocation(location) {
    form.camp_location = location === 'clubhuis' ? 'clubhuis' : 'fram';
    ensureExpenseRowsForLocation();
}

ensureExpenseRowsForLocation();

function isAutoContributionRow(sectionTitle, label) {
    const section = String(sectionTitle || '').trim().toLowerCase();
    const rowLabel = String(label || '').trim().toLowerCase();
    if (section === 'bijdragen') {
        return rowLabel.includes('leiding') || rowLabel.includes('jeugdleden') || rowLabel.includes('jeugdlid');
    }
    if (section === 'uitgaven') {
        return rowLabel.includes('geschatte vaaruren')
            || rowLabel.includes('geschatte aggregaaturen')
            || rowLabel.includes('geschatte aggregraaturen');
    }
    return false;
}

function rowAmountDisplayValue(row, sectionTitle) {
    if (isAutoContributionRow(sectionTitle, row?.label)) {
        return moneyInputPreview(effectiveAmount(row, sectionTitle), { fallback: '0,00' });
    }
    return moneyInputPreview(row?.amount, { fallback: '' });
}

const totals = computed(() => {
    const incomeTitles = ['bijdragen', 'overige bijdragen'];
    const expenseTitles = ['uitgaven', 'overige uitgaven'];
    let income = 0;
    let expenses = 0;
    for (const section of form.budget_sections || []) {
        const sum = sectionTotal(section);
        const title = String(section.title || '').trim().toLowerCase();
        if (incomeTitles.includes(title)) income += sum;
        if (expenseTitles.includes(title)) expenses += sum;
    }
    return { income, expenses, difference: income - expenses };
});

function generatePdf() {
    if (!isEdit.value) return;
    router.post(route('camp-budgets.pdf', props.item.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`${speltakLabel} - ${isEdit ? 'Begroting bewerken' : 'Begroting toevoegen'}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - {{ isEdit ? 'Begroting bewerken' : 'Begroting toevoegen' }}</h2>
                <Link :href="route('camp-budgets.index')" class="btn-action-back" title="Terug" aria-label="Terug">
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark" @submit.prevent="submit('save')">
            <div class="flex items-center gap-2">
                <span :class="['rounded-full px-2 py-0.5 text-xs', statusClass(currentStatus)]">
                    {{ statusLabel(currentStatus) }}
                </span>
                <p v-if="source.review_note" class="text-xs text-amber-700 dark:text-amber-300">
                    Opmerking bestuur: {{ source.review_note }}
                </p>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="space-y-1">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-app-muted-dark">Jaar</label>
                    <input v-model="form.camp_year" type="number" min="2020" max="2100" class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" required />
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-app-muted-dark">Kampdagen</label>
                    <input v-model.number="form.camp_days" type="number" min="1" max="60" step="1" inputmode="numeric" class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" required @input="form.camp_days = normalizedCampDays(form.camp_days)" />
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-app-muted-dark">Titel</label>
                    <input v-model="form.title" type="text" class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" required />
                </div>
                <div class="space-y-1 sm:col-span-3">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-app-muted-dark">Notitie</label>
                    <textarea v-model="form.content" rows="3" class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Korte toelichting..." />
                </div>
            </div>

            <div class="rounded-xl border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-app-canvas-dark">
                <h3 class="mb-2 text-sm font-semibold text-app-ink dark:text-app-ink-dark">Standaardwaarden</h3>
                <div class="mb-3">
                    <div class="inline-flex items-center rounded-full border border-app-border bg-slate-100 p-1 dark:border-app-border-dark dark:bg-slate-800">
                        <button
                            type="button"
                            class="rounded-full px-4 py-1.5 text-xs font-semibold transition"
                            :class="campLocation === 'clubhuis' ? 'bg-brand-blue text-white shadow-sm' : 'text-app-ink dark:text-app-ink-dark'"
                            @click="setCampLocation('clubhuis')"
                        >
                            Clubhuis
                        </button>
                        <button
                            type="button"
                            class="rounded-full px-4 py-1.5 text-xs font-semibold transition"
                            :class="campLocation === 'fram' ? 'bg-brand-blue text-white shadow-sm' : 'text-app-ink dark:text-app-ink-dark'"
                            @click="setCampLocation('fram')"
                        >
                            Fram
                        </button>
                    </div>
                </div>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <label v-if="campLocation === 'clubhuis'" class="text-xs text-app-ink dark:text-app-ink-dark">Prijs per dag clubhuis <div class="relative mt-1"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center rounded-l border-r border-app-border bg-slate-100 px-2 font-semibold text-slate-700 dark:border-app-border-dark dark:bg-slate-800 dark:text-app-ink-dark">€</span><input :value="moneyInputPreview(form.standard_values.prijs_per_dag_clubhuis, { fallback: '0,00' })" type="text" inputmode="decimal" class="w-full rounded border border-app-border bg-white py-1.5 pl-8 pr-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" @input="onStandardMoneyInput('prijs_per_dag_clubhuis', $event)" /></div></label>
                    <label v-if="campLocation === 'fram'" class="text-xs text-app-ink dark:text-app-ink-dark">Kosten vaart p/u <div class="relative mt-1"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center rounded-l border-r border-app-border bg-slate-100 px-2 font-semibold text-slate-700 dark:border-app-border-dark dark:bg-slate-800 dark:text-app-ink-dark">€</span><input :value="moneyInputPreview(form.standard_values.kosten_vaart_pu, { fallback: '0,00' })" type="text" inputmode="decimal" class="w-full rounded border border-app-border bg-white py-1.5 pl-8 pr-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" @input="onStandardMoneyInput('kosten_vaart_pu', $event)" /></div></label>
                    <label v-if="campLocation === 'fram'" class="text-xs text-app-ink dark:text-app-ink-dark">Kosten aggregaat p/u <div class="relative mt-1"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center rounded-l border-r border-app-border bg-slate-100 px-2 font-semibold text-slate-700 dark:border-app-border-dark dark:bg-slate-800 dark:text-app-ink-dark">€</span><input :value="moneyInputPreview(form.standard_values.kosten_aggregaat_pu, { fallback: '0,00' })" type="text" inputmode="decimal" class="w-full rounded border border-app-border bg-white py-1.5 pl-8 pr-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" @input="onStandardMoneyInput('kosten_aggregaat_pu', $event)" /></div></label>
                    <label v-if="campLocation === 'fram'" class="text-xs text-app-ink dark:text-app-ink-dark">Huur Fram pppd <div class="relative mt-1"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center rounded-l border-r border-app-border bg-slate-100 px-2 font-semibold text-slate-700 dark:border-app-border-dark dark:bg-slate-800 dark:text-app-ink-dark">€</span><input :value="moneyInputPreview(form.standard_values.huur_fram_pppd, { fallback: '0,00' })" type="text" inputmode="decimal" class="w-full rounded border border-app-border bg-white py-1.5 pl-8 pr-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" @input="onStandardMoneyInput('huur_fram_pppd', $event)" /></div></label>
                    <label class="text-xs text-app-ink dark:text-app-ink-dark">Prijs per dag leiding <div class="relative mt-1"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center rounded-l border-r border-app-border bg-slate-100 px-2 font-semibold text-slate-700 dark:border-app-border-dark dark:bg-slate-800 dark:text-app-ink-dark">€</span><input :value="moneyInputPreview(form.standard_values.prijs_per_dag_leiding, { fallback: '0,00' })" type="text" inputmode="decimal" class="w-full rounded border border-app-border bg-white py-1.5 pl-8 pr-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" @input="onStandardMoneyInput('prijs_per_dag_leiding', $event)" /></div></label>
                    <label class="text-xs text-app-ink dark:text-app-ink-dark">Prijs per dag jeugdlid <div class="relative mt-1"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center rounded-l border-r border-app-border bg-slate-100 px-2 font-semibold text-slate-700 dark:border-app-border-dark dark:bg-slate-800 dark:text-app-ink-dark">€</span><input :value="moneyInputPreview(form.standard_values.prijs_per_dag_jeugdlid, { fallback: '0,00' })" type="text" inputmode="decimal" class="w-full rounded border border-app-border bg-white py-1.5 pl-8 pr-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" @input="onStandardMoneyInput('prijs_per_dag_jeugdlid', $event)" /></div></label>
                    <label class="text-xs text-app-ink dark:text-app-ink-dark">Proviand pppd <div class="relative mt-1"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center rounded-l border-r border-app-border bg-slate-100 px-2 font-semibold text-slate-700 dark:border-app-border-dark dark:bg-slate-800 dark:text-app-ink-dark">€</span><input :value="moneyInputPreview(form.standard_values.proviand_pppd, { fallback: '0,00' })" type="text" inputmode="decimal" class="w-full rounded border border-app-border bg-white py-1.5 pl-8 pr-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" @input="onStandardMoneyInput('proviand_pppd', $event)" /></div></label>
                    <label class="text-xs text-app-ink dark:text-app-ink-dark">Groepsafdracht pjpd <div class="relative mt-1"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center rounded-l border-r border-app-border bg-slate-100 px-2 font-semibold text-slate-700 dark:border-app-border-dark dark:bg-slate-800 dark:text-app-ink-dark">€</span><input :value="moneyInputPreview(form.standard_values.groepsafdracht_pjpd, { fallback: '0,00' })" type="text" inputmode="decimal" class="w-full rounded border border-app-border bg-white py-1.5 pl-8 pr-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" @input="onStandardMoneyInput('groepsafdracht_pjpd', $event)" /></div></label>
                    <label class="text-xs text-app-ink dark:text-app-ink-dark">Reservering NaWaKa pjpd <div class="relative mt-1"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center rounded-l border-r border-app-border bg-slate-100 px-2 font-semibold text-slate-700 dark:border-app-border-dark dark:bg-slate-800 dark:text-app-ink-dark">€</span><input :value="moneyInputPreview(form.standard_values.reservering_nawaka_pjpd, { fallback: '0,00' })" type="text" inputmode="decimal" class="w-full rounded border border-app-border bg-white py-1.5 pl-8 pr-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" @input="onStandardMoneyInput('reservering_nawaka_pjpd', $event)" /></div></label>
                </div>
            </div>

            <div class="space-y-3 rounded-xl border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-app-canvas-dark">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="(section, idx) in form.budget_sections"
                        :key="`section-tab-${idx}`"
                        type="button"
                        class="rounded-md border px-3 py-1.5 text-sm"
                        :class="idx === activeSectionIndex ? 'border-brand-blue bg-brand-blue/10 text-app-ink dark:text-app-ink-dark' : 'border-app-border bg-white text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark'"
                        @click="activeSectionIndex = idx"
                    >
                        {{ section.title || `Sectie ${idx + 1}` }}
                    </button>
                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-white" title="Sectie toevoegen" @click="addSection">
                        <PlusIcon class="h-4 w-4" />
                    </button>
                </div>

                <div v-if="form.budget_sections[activeSectionIndex]" class="space-y-3">
                    <div class="flex items-center gap-2">
                        <input
                            v-model="form.budget_sections[activeSectionIndex].title"
                            type="text"
                            class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-slate-900 dark:text-app-ink-dark"
                            placeholder="Naam sectie"
                        />
                        <button type="button" class="btn-action-delete" title="Sectie verwijderen" @click="removeSection(activeSectionIndex)">
                            <TrashIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="overflow-x-auto rounded border border-app-border">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-app-ink dark:bg-slate-900 dark:text-app-ink-dark">
                                <tr>
                                    <th class="px-2 py-2 text-left">Post</th>
                                    <th class="px-2 py-2 text-left">Aantal</th>
                                    <th class="px-2 py-2 text-left">Bedrag</th>
                                    <th class="px-2 py-2 text-left">Notitie</th>
                                    <th class="px-2 py-2 text-left">Totaal</th>
                                    <th class="px-2 py-2 text-left">Actie</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-app-border bg-white dark:divide-app-border-dark dark:bg-app-canvas-dark">
                                <tr v-for="(row, rowIdx) in form.budget_sections[activeSectionIndex].rows" :key="`row-${rowIdx}`">
                                    <td class="px-2 py-2">
                                        <input v-model="row.label" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-black dark:border-app-border-dark dark:bg-slate-900 dark:text-app-ink-dark" />
                                    </td>
                                    <td class="px-2 py-2">
                                        <input v-model.number="row.quantity" type="number" min="0" step="1" inputmode="numeric" class="w-24 rounded border border-app-border bg-white px-2 py-1.5 text-black dark:border-app-border-dark dark:bg-slate-900 dark:text-app-ink-dark" @input="row.quantity = normalizeWholeNumber(row.quantity)" />
                                    </td>
                                    <td class="px-2 py-2">
                                        <div class="relative w-36">
                                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center rounded-l border-r border-app-border bg-slate-100 px-2 font-semibold text-slate-700 dark:border-app-border-dark dark:bg-slate-800 dark:text-app-ink-dark">€</span>
                                            <input :value="rowAmountDisplayValue(row, form.budget_sections[activeSectionIndex].title)" type="text" inputmode="decimal" :readonly="isAutoContributionRow(form.budget_sections[activeSectionIndex].title, row.label)" class="w-full rounded border border-app-border bg-white py-1.5 pl-8 pr-2 text-black dark:border-app-border-dark dark:bg-slate-900 dark:text-app-ink-dark readonly:bg-slate-100 readonly:text-slate-700 dark:readonly:bg-slate-800 dark:readonly:text-app-ink-dark" @input="onRowAmountInput(row, $event)" />
                                        </div>
                                    </td>
                                    <td class="px-2 py-2">
                                        <input v-model="row.note" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-black dark:border-app-border-dark dark:bg-slate-900 dark:text-app-ink-dark" />
                                    </td>
                                    <td class="px-2 py-2">
                                        <span class="text-xs text-app-ink dark:text-app-ink-dark">€ {{ formatMoney(normalizeWholeNumber(row.quantity) * effectiveAmount(row, form.budget_sections[activeSectionIndex].title)) }}</span>
                                    </td>
                                    <td class="px-2 py-2">
                                        <button type="button" class="btn-action-delete" title="Regel verwijderen" @click="removeRow(activeSectionIndex, rowIdx)">
                                            <TrashIcon class="h-5 w-5" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex items-center justify-between">
                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-white" title="Regel toevoegen" @click="addRow(activeSectionIndex)">
                            <PlusIcon class="h-4 w-4" />
                        </button>
                        <p class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Sectietotaal: € {{ formatMoney(sectionTotal(form.budget_sections[activeSectionIndex])) }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-2 rounded-xl border border-app-border bg-white p-3 sm:grid-cols-3 dark:border-app-border-dark dark:bg-app-canvas-dark">
                <p class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Totaal bijdragen: € {{ formatMoney(totals.income) }}</p>
                <p class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Totaal uitgaven: € {{ formatMoney(totals.expenses) }}</p>
                <p class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Verschil: € {{ formatMoney(totals.difference) }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 border-t border-app-border pt-3">
                <button type="submit" class="btn-action-save" :disabled="form.processing" title="Opslaan" aria-label="Opslaan">
                    <DocumentCheckIcon class="h-5 w-5" />
                </button>
                <button type="button" class="btn-action-save" :disabled="form.processing" title="Begroting inleveren" aria-label="Begroting inleveren" @click="submitForReview">
                    <PaperAirplaneIcon class="h-5 w-5" />
                </button>
                <button v-if="isEdit" type="button" class="btn-action-save" title="PDF maken en opslaan" aria-label="PDF maken en opslaan" @click="generatePdf">
                    <DocumentArrowDownIcon class="h-5 w-5" />
                </button>
                <a
                    v-if="isEdit && item?.pdf_path"
                    :href="route('camp-budgets.pdf.download', item.id)"
                    class="btn-action-save"
                    title="PDF downloaden"
                    aria-label="PDF downloaden"
                >
                    <ArrowDownTrayIcon class="h-5 w-5" />
                </a>
                <button v-if="isEdit && canCreate" type="button" class="btn-action-save" title="Kopie maken" aria-label="Kopie maken" @click="copyItem">
                    <DocumentDuplicateIcon class="h-5 w-5" />
                </button>
                <button v-if="isEdit && canUpdate" type="button" class="btn-action-delete" title="Verwijderen" aria-label="Verwijderen" @click="destroyItem">
                    <TrashIcon class="h-5 w-5" />
                </button>
            </div>
            <div v-if="Object.keys(form.errors).length" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-200">
                <p class="font-semibold">Opslaan mislukt:</p>
                <ul class="mt-1 list-disc pl-4">
                    <li v-for="(message, key) in form.errors" :key="`budget-error-${key}`">
                        {{ message }}
                    </li>
                </ul>
            </div>
        </form>
    </AuthenticatedLayout>

    <AppConfirmModal
        :show="showDeleteModal"
        title="Begroting verwijderen?"
        :message="props.item ? `Weet je zeker dat je begroting '${props.item.title}' wilt verwijderen?` : ''"
        confirm-text="Ja, verwijderen"
        cancel-text="Annuleren"
        @close="closeDeleteModal"
        @confirm="confirmDeleteItem"
    />
</template>
