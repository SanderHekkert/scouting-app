<script setup>
import { computed, ref } from 'vue';
import { formatMoney, sanitizeMoneyInput } from '@/utils/money';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import CampBudgetSectionsEditor from '@/Pages/CampBudgets/Partials/CampBudgetSectionsEditor.vue';
import CampBudgetStandardValuesPanel from '@/Pages/CampBudgets/Partials/CampBudgetStandardValuesPanel.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon, DocumentCheckIcon, PaperAirplaneIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    mode: { type: String, default: 'create' },
    item: { type: Object, default: null },
    copyItem: { type: Object, default: null },
    defaultSections: { type: Array, default: () => [] },
    defaultStandardValues: { type: Object, default: () => ({}) },
});

const page = usePage();
const perms = computed(() => page.props.auth?.permissions?.camp_budgets ?? {});
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
        clubhuis_bedrag: Number(initialStandardValues.clubhuis_bedrag ?? initialStandardValues.prijs_per_dag_clubhuis ?? 0),
        borg_bedrag: Number(initialStandardValues.borg_bedrag ?? 0),
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
const moneyDrafts = ref({});

function normalizedCampDays(value) {
    const parsed = Number.parseInt(String(value ?? '').replace(/[^\d-]/g, ''), 10);
    if (Number.isNaN(parsed) || parsed < 1) return 1;
    return Math.min(parsed, 60);
}

function statusLabel(status) {
    if (status === 'submitted') return 'Wacht op goedkeuring';
    if (status === 'approved') return 'Goedgekeurd';
    if (status === 'needs_changes') return 'Aanpassing(en) nodig';
    return 'Concept';
}

function statusClass(status) {
    if (status === 'approved') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300';
    if (status === 'submitted') return 'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-300';
    if (status === 'needs_changes') return 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300';
    return 'bg-slate-100 text-slate-700 dark:bg-slate-700/40 dark:text-slate-200';
}

function submit(action = 'save') {
    enforceFixedQuantityRows();
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

function isFixedQuantityFormulaRow(sectionTitle, label) {
    const section = String(sectionTitle || '').trim().toLowerCase();
    const rowLabel = String(label || '').trim().toLowerCase();
    if (section !== 'uitgaven') return false;
    return rowLabel.includes('proviand')
        || rowLabel.includes('clubhuis')
        || rowLabel.includes('huur fram')
        || rowLabel.includes('groepsafdracht')
        || rowLabel.includes('nawaka')
        || rowLabel.includes('borg');
}

function upsertExpenseRow(label) {
    const expensesSection = (form.budget_sections || []).find((section) => String(section?.title || '').trim().toLowerCase() === 'uitgaven');
    if (!expensesSection) return;
    const existing = (expensesSection.rows || []).some((row) => String(row?.label || '').trim().toLowerCase() === label.toLowerCase());
    if (existing) return;
    expensesSection.rows.push({
        label,
        quantity: isFixedQuantityFormulaRow('uitgaven', label) ? 1 : 0,
        amount: 0,
        note: '',
    });
}

function reorderExpenseRowsForFram() {
    const expensesSection = (form.budget_sections || []).find((section) => String(section?.title || '').trim().toLowerCase() === 'uitgaven');
    if (!expensesSection) return;

    const rows = Array.isArray(expensesSection.rows) ? [...expensesSection.rows] : [];
    const sortWeight = (label) => {
        const key = String(label || '').trim().toLowerCase();
        if (key === 'huur fram') return 0;
        if (key.includes('geschatte vaaruren')) return 1;
        return 2;
    };

    rows.sort((a, b) => sortWeight(a?.label) - sortWeight(b?.label));
    expensesSection.rows = rows;
}

function ensureExpenseRowsForLocation() {
    if (campLocation.value === 'fram') {
        upsertExpenseRow('Huur Fram');
        upsertExpenseRow('Geschatte vaaruren');
        upsertExpenseRow('Geschatte aggregaaturen');
        upsertExpenseRow('Reservering NaWaKa');
        upsertExpenseRow('Proviand');
        upsertExpenseRow('Groepsafdracht');
        reorderExpenseRowsForFram();
        reorderExpenseRowsForLocation();
        return;
    }

    upsertExpenseRow('Kosten uitje');
    upsertExpenseRow('Clubhuis');
    upsertExpenseRow('Borg');
    upsertExpenseRow('Proviand');
    upsertExpenseRow('Groepsafdracht');
    upsertExpenseRow('Reservering NaWaKa');
    reorderExpenseRowsForLocation();
}

function enforceFixedQuantityRows() {
    for (const section of form.budget_sections || []) {
        const sectionTitle = String(section?.title || '');
        for (const row of section?.rows || []) {
            if (isFixedQuantityFormulaRow(sectionTitle, row?.label)) {
                row.quantity = 1;
            }
        }
    }
}

function normalizeWholeNumber(value) {
    const parsed = Number.parseInt(String(value ?? '').replace(/[^\d-]/g, ''), 10);
    if (Number.isNaN(parsed)) return 0;
    return Math.max(0, parsed);
}

function onRowQuantityInput(row, sectionTitle) {
    if (isFixedQuantityFormulaRow(sectionTitle, row?.label)) {
        row.quantity = 1;
        return;
    }
    row.quantity = normalizeWholeNumber(row.quantity);
}

function removeRow(sectionIndex, rowIndex) {
    form.budget_sections[sectionIndex].rows.splice(rowIndex, 1);
}

function sectionTotal(section) {
    return (section?.rows || []).reduce((sum, row) => {
        if (isBorgRow(section?.title, row?.label)) {
            return sum;
        }
        return sum + rowComputedTotal(row, section?.title);
    }, 0);
}

function effectiveAmount(row, sectionTitle) {
    const label = String(row?.label || '').trim().toLowerCase();
    const section = String(sectionTitle || '').trim().toLowerCase();
    const manualAmount = Number(row?.amount || 0) || 0;
    if (!label) return manualAmount;
    const days = normalizedCampDays(form.camp_days);

    if (section === 'bijdragen' && label.includes('leiding')) return (Number(form.standard_values.prijs_per_dag_leiding) || 0) * days;
    if (section === 'bijdragen' && (label.includes('jeugdleden') || label.includes('jeugdlid'))) return Number(form.standard_values.prijs_per_dag_jeugdlid) || 0;
    if (label.includes('clubhuis')) return Number(form.standard_values.clubhuis_bedrag) || 0;
    if (label.includes('borg')) return Number(form.standard_values.borg_bedrag) || 0;
    if (section === 'uitgaven' && label.includes('vaar')) return Number(form.standard_values.kosten_vaart_pu) || 0;
    if (section === 'uitgaven' && label.includes('aggreg')) return Number(form.standard_values.kosten_aggregaat_pu) || 0;
    if (label.includes('fram')) {
        if (campLocation.value === 'clubhuis') return Number(form.standard_values.clubhuis_bedrag) || 0;
        return (Number(form.standard_values.huur_fram_pppd) || 0) * days;
    }
    if (label.includes('proviand')) {
        if (manualAmount > 0) return manualAmount;
        return (Number(form.standard_values.proviand_pppd) || 0) * days;
    }
    if (label.includes('groepsafdracht')) return (Number(form.standard_values.groepsafdracht_pjpd) || 0) * days;
    if (label.includes('nawaka')) return (Number(form.standard_values.reservering_nawaka_pjpd) || 0) * days;
    if (manualAmount > 0) return manualAmount;
    return manualAmount;
}

function standardMoneyKey(field) {
    return `standard:${field}`;
}

function rowMoneyKey(sectionIndex, rowIndex) {
    return `row:${sectionIndex}:${rowIndex}`;
}

function hasMoneyDraft(key) {
    return Object.prototype.hasOwnProperty.call(moneyDrafts.value, key);
}

function setMoneyDraft(key, value) {
    moneyDrafts.value[key] = String(value ?? '');
}

function clearMoneyDraft(key) {
    if (!hasMoneyDraft(key)) return;
    const nextDrafts = { ...moneyDrafts.value };
    delete nextDrafts[key];
    moneyDrafts.value = nextDrafts;
}

function onStandardMoneyFocus(field) {
    const key = standardMoneyKey(field);
    setMoneyDraft(key, moneyInputPreview(form.standard_values[field], { fallback: '' }));
}

function onStandardMoneyInput(field, event) {
    const key = standardMoneyKey(field);
    const raw = String(event?.target?.value ?? '');
    setMoneyDraft(key, raw);
    const sanitized = sanitizeMoneyInput(raw, { allowEmpty: true });
    form.standard_values[field] = sanitized === '' ? '' : sanitized;
}

function onStandardMoneyBlur(field) {
    const key = standardMoneyKey(field);
    const raw = hasMoneyDraft(key) ? moneyDrafts.value[key] : form.standard_values[field];
    const sanitized = sanitizeMoneyInput(raw, { allowEmpty: false });
    const numeric = Number.parseFloat(sanitized || '0');
    form.standard_values[field] = Number.isFinite(numeric) ? numeric.toFixed(2) : '0.00';
    clearMoneyDraft(key);
}

function onRowAmountFocus(row, sectionIndex, rowIndex) {
    const key = rowMoneyKey(sectionIndex, rowIndex);
    setMoneyDraft(key, moneyInputPreview(row?.amount, { fallback: '' }));
}

function onRowAmountInput(row, event, sectionIndex, rowIndex) {
    const key = rowMoneyKey(sectionIndex, rowIndex);
    const raw = String(event?.target?.value ?? '');
    setMoneyDraft(key, raw);
    const sanitized = sanitizeMoneyInput(raw, { allowEmpty: true });
    row.amount = sanitized === '' ? '' : sanitized;
}

function onRowAmountBlur(row, sectionIndex, rowIndex) {
    const key = rowMoneyKey(sectionIndex, rowIndex);
    const raw = hasMoneyDraft(key) ? moneyDrafts.value[key] : row?.amount;
    const sanitized = sanitizeMoneyInput(raw, { allowEmpty: false });
    const numeric = Number.parseFloat(sanitized || '0');
    row.amount = Number.isFinite(numeric) ? numeric.toFixed(2) : '0.00';
    clearMoneyDraft(key);
}

function moneyInputPreview(value, { fallback = '' } = {}) {
    if (value === null || value === undefined || value === '') return fallback;
    return String(value).replace('.', ',');
}

function setCampLocation(location) {
    form.camp_location = location === 'clubhuis' ? 'clubhuis' : 'fram';
    ensureExpenseRowsForLocation();
    enforceFixedQuantityRows();
}

ensureExpenseRowsForLocation();
enforceFixedQuantityRows();

function isAutoContributionRow(sectionTitle, label) {
    const section = String(sectionTitle || '').trim().toLowerCase();
    const rowLabel = String(label || '').trim().toLowerCase();
    if (section === 'bijdragen') {
        return rowLabel.includes('leiding') || rowLabel.includes('jeugdleden') || rowLabel.includes('jeugdlid');
    }
    if (section === 'uitgaven') {
        return rowLabel.includes('vaar')
            || rowLabel.includes('aggreg')
            || rowLabel.includes('clubhuis')
            || rowLabel.includes('huur fram')
            || rowLabel.includes('proviand')
            || rowLabel.includes('groepsafdracht')
            || rowLabel.includes('nawaka')
            || rowLabel.includes('borg');
    }
    return false;
}

function isProviandFormulaRow(sectionTitle, label) {
    const section = String(sectionTitle || '').trim().toLowerCase();
    const rowLabel = String(label || '').trim().toLowerCase();
    return section === 'uitgaven' && rowLabel.includes('proviand');
}

function isFramRentFormulaRow(sectionTitle, label) {
    const section = String(sectionTitle || '').trim().toLowerCase();
    const rowLabel = String(label || '').trim().toLowerCase();
    return section === 'uitgaven' && rowLabel.includes('huur fram');
}

function isGroepsafdrachtFormulaRow(sectionTitle, label) {
    const section = String(sectionTitle || '').trim().toLowerCase();
    const rowLabel = String(label || '').trim().toLowerCase();
    return section === 'uitgaven' && rowLabel.includes('groepsafdracht');
}

function isReserveringNawakaFormulaRow(sectionTitle, label) {
    const section = String(sectionTitle || '').trim().toLowerCase();
    const rowLabel = String(label || '').trim().toLowerCase();
    return section === 'uitgaven' && rowLabel.includes('nawaka');
}

function isBorgRow(sectionTitle, label) {
    const section = String(sectionTitle || '').trim().toLowerCase();
    const rowLabel = String(label || '').trim().toLowerCase();
    return section === 'uitgaven' && rowLabel.includes('borg');
}

function isEstimatedHoursRow(sectionTitle, label) {
    const section = String(sectionTitle || '').trim().toLowerCase();
    const rowLabel = String(label || '').trim().toLowerCase();
    if (section !== 'uitgaven') return false;
    return rowLabel.includes('vaar') || rowLabel.includes('aggreg');
}

function jeugdledenCountFromBudgetSections() {
    const contributions = (form.budget_sections || []).find((section) => String(section?.title || '').trim().toLowerCase() === 'bijdragen');
    if (!contributions) return 0;

    return (contributions.rows || []).reduce((sum, row) => {
        const label = String(row?.label || '').trim().toLowerCase();
        if (label.includes('jeugdleden') || label.includes('jeugdlid')) {
            return sum + normalizeWholeNumber(row?.quantity);
        }
        return sum;
    }, 0);
}

function participantCountFromBudgetSections() {
    const contributions = (form.budget_sections || []).find((section) => String(section?.title || '').trim().toLowerCase() === 'bijdragen');
    if (!contributions) return 0;

    return (contributions.rows || []).reduce((sum, row) => {
        const label = String(row?.label || '').trim().toLowerCase();
        if (label.includes('leiding') || label.includes('jeugdleden') || label.includes('jeugdlid')) {
            return sum + normalizeWholeNumber(row?.quantity);
        }
        return sum;
    }, 0);
}

function rowComputedTotal(row, sectionTitle) {
    if (isProviandFormulaRow(sectionTitle, row?.label)) {
        const manualAmount = Number(row?.amount || 0) || 0;
        if (manualAmount > 0) {
            const quantity = normalizeWholeNumber(row?.quantity);
            return quantity * manualAmount;
        }
        const participants = participantCountFromBudgetSections();
        const days = normalizedCampDays(form.camp_days);
        const proviandPerDay = Number(form.standard_values.proviand_pppd) || 0;
        return participants * proviandPerDay * days;
    }
    if (isFramRentFormulaRow(sectionTitle, row?.label)) {
        const participants = participantCountFromBudgetSections();
        const framPppd = Number(form.standard_values.huur_fram_pppd) || 0;
        const days = normalizedCampDays(form.camp_days);
        return participants * framPppd * days;
    }
    if (isGroepsafdrachtFormulaRow(sectionTitle, row?.label)) {
        const jeugdleden = jeugdledenCountFromBudgetSections();
        const days = normalizedCampDays(form.camp_days);
        const groepsafdrachtPjpd = Number(form.standard_values.groepsafdracht_pjpd) || 0;
        return jeugdleden * groepsafdrachtPjpd * days;
    }
    if (isReserveringNawakaFormulaRow(sectionTitle, row?.label)) {
        const jeugdleden = jeugdledenCountFromBudgetSections();
        const days = normalizedCampDays(form.camp_days);
        const reserveringNawakaPjpd = Number(form.standard_values.reservering_nawaka_pjpd) || 0;
        return jeugdleden * reserveringNawakaPjpd * days;
    }

    const quantity = normalizeWholeNumber(row?.quantity);
    const amount = effectiveAmount(row, sectionTitle);
    return quantity * amount;
}

function rowAmountDisplayValue(row, sectionTitle) {
    if (isAutoContributionRow(sectionTitle, row?.label)) {
        if (isProviandFormulaRow(sectionTitle, row?.label)) {
            return formatMoney(rowComputedTotal(row, sectionTitle));
        }
        if (isFramRentFormulaRow(sectionTitle, row?.label)) {
            return formatMoney(rowComputedTotal(row, sectionTitle));
        }
        if (isGroepsafdrachtFormulaRow(sectionTitle, row?.label)) {
            return formatMoney(rowComputedTotal(row, sectionTitle));
        }
        if (isReserveringNawakaFormulaRow(sectionTitle, row?.label)) {
            return formatMoney(rowComputedTotal(row, sectionTitle));
        }
        if (isBorgRow(sectionTitle, row?.label)) {
            return formatMoney(rowComputedTotal(row, sectionTitle));
        }
        if (isEstimatedHoursRow(sectionTitle, row?.label)) {
            return formatMoney(rowComputedTotal(row, sectionTitle));
        }

        const quantity = normalizeWholeNumber(row?.quantity);
        if (quantity < 1) {
            return '0,00';
        }
        return formatMoney(effectiveAmount(row, sectionTitle));
    }
    return moneyInputPreview(row?.amount, { fallback: '' });
}

function reorderExpenseRowsForLocation() {
    const expensesSection = (form.budget_sections || []).find((section) => String(section?.title || '').trim().toLowerCase() === 'uitgaven');
    if (!expensesSection) return;

    const rows = Array.isArray(expensesSection.rows) ? [...expensesSection.rows] : [];
    const proviandIndex = rows.findIndex((row) => String(row?.label || '').trim().toLowerCase().includes('proviand'));
    const groepsafdrachtIndex = rows.findIndex((row) => String(row?.label || '').trim().toLowerCase().includes('groepsafdracht'));
    const nawakaIndex = rows.findIndex((row) => String(row?.label || '').trim().toLowerCase().includes('nawaka'));
    const clubhuisIndex = rows.findIndex((row) => String(row?.label || '').trim().toLowerCase().includes('clubhuis'));
    const borgIndex = rows.findIndex((row) => String(row?.label || '').trim().toLowerCase().includes('borg'));
    if (clubhuisIndex !== -1 && borgIndex !== -1 && borgIndex !== clubhuisIndex + 1) {
        const [borgRow] = rows.splice(borgIndex, 1);
        const targetClubhuisIndex = rows.findIndex((row) => String(row?.label || '').trim().toLowerCase().includes('clubhuis'));
        rows.splice(Math.max(0, targetClubhuisIndex + 1), 0, borgRow);
    }
    if (proviandIndex === -1 || groepsafdrachtIndex === -1) {
        return;
    }
    if (groepsafdrachtIndex !== proviandIndex + 1) {
        const [groepsafdrachtRow] = rows.splice(groepsafdrachtIndex, 1);
        const targetProviandIndex = rows.findIndex((row) => String(row?.label || '').trim().toLowerCase().includes('proviand'));
        rows.splice(Math.max(0, targetProviandIndex + 1), 0, groepsafdrachtRow);
    }
    if (nawakaIndex !== -1) {
        const adjustedNawakaIndex = rows.findIndex((row) => String(row?.label || '').trim().toLowerCase().includes('nawaka'));
        const adjustedGroepsafdrachtIndex = rows.findIndex((row) => String(row?.label || '').trim().toLowerCase().includes('groepsafdracht'));
        if (adjustedNawakaIndex !== adjustedGroepsafdrachtIndex + 1) {
            const [nawakaRow] = rows.splice(adjustedNawakaIndex, 1);
            rows.splice(Math.max(0, adjustedGroepsafdrachtIndex + 1), 0, nawakaRow);
        }
    }
    expensesSection.rows = rows;
}

function rowAmountInputValue(row, sectionTitle, sectionIndex, rowIndex) {
    const key = rowMoneyKey(sectionIndex, rowIndex);
    if (hasMoneyDraft(key)) return moneyDrafts.value[key];
    return rowAmountDisplayValue(row, sectionTitle);
}

function isAmountReadOnly(sectionTitle, label) {
    if (isProviandFormulaRow(sectionTitle, label)) {
        return false;
    }
    return isAutoContributionRow(sectionTitle, label);
}

function standardAmountInputValue(field, fallback = '0,00') {
    const key = standardMoneyKey(field);
    if (hasMoneyDraft(key)) return moneyDrafts.value[key];
    const value = form.standard_values[field];
    if (value === null || value === undefined || value === '') return fallback;
    return formatMoney(value);
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
            </div>

            <CampBudgetStandardValuesPanel
                :camp-location="campLocation"
                :standard-amount-input-value="standardAmountInputValue"
                :on-standard-money-focus="onStandardMoneyFocus"
                :on-standard-money-input="onStandardMoneyInput"
                :on-standard-money-blur="onStandardMoneyBlur"
                @set-camp-location="setCampLocation"
            />

            <CampBudgetSectionsEditor
                :form="form"
                :active-section-index="activeSectionIndex"
                :add-section="addSection"
                :remove-section="removeSection"
                :add-row="addRow"
                :remove-row="removeRow"
                :is-fixed-quantity-formula-row="isFixedQuantityFormulaRow"
                :on-row-quantity-input="onRowQuantityInput"
                :row-amount-input-value="rowAmountInputValue"
                :is-amount-read-only="isAmountReadOnly"
                :on-row-amount-focus="onRowAmountFocus"
                :on-row-amount-input="onRowAmountInput"
                :on-row-amount-blur="onRowAmountBlur"
                :row-computed-total="rowComputedTotal"
                :section-total="sectionTotal"
                :format-money="formatMoney"
                @update:active-section-index="activeSectionIndex = $event"
            />

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
