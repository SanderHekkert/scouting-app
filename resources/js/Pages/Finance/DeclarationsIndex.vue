<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    pots: { type: Array, default: () => [] },
    declarations: { type: Array, default: () => [] },
});

const page = usePage();
const csrf = computed(() => page.props.csrf_token || '');
const sectionLabelMap = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const speltakLabel = computed(() => sectionLabelMap[page.props.auth?.active_section] || 'Dolfijnen');

const declarationForm = useForm({
    pot_id: '',
    amount: '',
    iban: '',
    account_name: '',
    description_total: '',
    description_lines: '',
    declared_at: '',
    receipt_file: null,
});

const ocrLoading = ref(false);
const cameraReceiptInput = ref(null);
const galleryReceiptInput = ref(null);
const receiptRows = ref([{ name: '', quantity: '1', amount: '', vat: '21' }]);
const hasActivePots = computed(() => props.pots.length > 0);

function formatCurrency(value) {
    const amount = Number(value || 0);
    return new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR' }).format(amount);
}

function normalizeRow(row) {
    return {
        name: String(row?.name || '').trim(),
        quantity: String(row?.quantity || '').trim(),
        amount: String(row?.amount || '').trim(),
        vat: String(row?.vat || '').trim(),
    };
}

function buildDescriptionLinesFromRows() {
    const rows = receiptRows.value
        .map((row) => normalizeRow(row))
        .filter((row) => row.name || row.amount || row.quantity || row.vat);
    if (!rows.length) return '';

    const header = 'Naam | Aantal | Bedrag | Btw%';
    const body = rows
        .map((row) => `${row.name || '-'} | ${row.quantity || '-'} | ${row.amount || '-'} | ${row.vat || '-'}`)
        .join('\n');

    return `${header}\n${body}`;
}

function parseNumber(value) {
    if (value === null || value === undefined || value === '') return 0;
    return Number.parseFloat(String(value).replace(',', '.')) || 0;
}

const receiptRowsTotal = computed(() => receiptRows.value.reduce((sum, row) => {
    const qty = parseNumber(row.quantity || 1) || 1;
    const amount = parseNumber(row.amount);
    return sum + (qty * amount);
}, 0));

function syncAmountFromRows() {
    if (!receiptRows.value.length) return;
    if (receiptRowsTotal.value <= 0) return;
    declarationForm.amount = receiptRowsTotal.value.toFixed(2);
}

function addReceiptRow() {
    receiptRows.value.push({ name: '', quantity: '1', amount: '', vat: '21' });
}

function removeReceiptRow(index) {
    if (receiptRows.value.length <= 1) return;
    receiptRows.value.splice(index, 1);
}

function openCameraUpload() {
    cameraReceiptInput.value?.click();
}

function openGalleryUpload() {
    galleryReceiptInput.value?.click();
}

async function onReceiptChange(event) {
    declarationForm.receipt_file = event?.target?.files?.[0] || null;
    if (!declarationForm.receipt_file) {
        receiptRows.value = [{ name: '', quantity: '1', amount: '', vat: '21' }];
        return;
    }

    await runOcrAssist();
}

async function runOcrAssist() {
    if (!declarationForm.receipt_file) return;
    ocrLoading.value = true;
    try {
        const formData = new FormData();
        formData.append('receipt_file', declarationForm.receipt_file);

        const response = await fetch(route('finance.ocr'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf.value,
                Accept: 'application/json',
            },
            body: formData,
        });
        if (!response.ok) return;
        const data = await response.json();
        const s = data?.suggestions || {};
        if (s.declared_at) declarationForm.declared_at = s.declared_at;
        if (Array.isArray(s.line_items) && s.line_items.length > 0) {
            receiptRows.value = s.line_items.map((row) => normalizeRow(row));
        }
        syncAmountFromRows();
        if (receiptRowsTotal.value <= 0 && s.amount != null) declarationForm.amount = String(s.amount);
        declarationForm.description_lines = buildDescriptionLinesFromRows();
    } finally {
        ocrLoading.value = false;
    }
}

function submitDeclaration() {
    declarationForm.description_lines = buildDescriptionLinesFromRows();
    declarationForm.post(route('finance.declarations.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            declarationForm.reset();
            receiptRows.value = [{ name: '', quantity: '1', amount: '', vat: '21' }];
        },
    });
}

function approve(id) {
    router.patch(route('finance.declarations.approve', id), {}, { preserveScroll: true });
}

function reject(id) {
    const review_note = prompt('Reden van afkeuren (optioneel):') || '';
    router.patch(route('finance.declarations.reject', id), { review_note }, { preserveScroll: true });
}

watch(receiptRows, () => {
    declarationForm.description_lines = buildDescriptionLinesFromRows();
    syncAmountFromRows();
}, { deep: true });
</script>

<template>
    <Head :title="`${speltakLabel} - Declaraties`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Declaraties</h2>
                <Link
                    :href="route('finance.declarations.create')"
                    class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800"
                >
                    Declaratie toevoegen
                </Link>
            </div>
        </template>

        <div class="space-y-4">
            <form class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark" @submit.prevent="submitDeclaration">
                <h4 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Nieuwe declaratie</h4>

                <p v-if="!hasActivePots" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                    Er is geen actief potje beschikbaar. Activeer eerst een potje om declaraties in te dienen.
                </p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Potje</label>
                        <select v-model="declarationForm.pot_id" class="w-full rounded border border-app-border bg-white px-3 py-2 text-black" required :disabled="!hasActivePots">
                            <option value="" disabled>Kies potje</option>
                            <option v-for="pot in props.pots" :key="`declaration-pot-${pot.id}`" :value="pot.id">
                                {{ pot.name }} ({{ formatCurrency(pot.current_amount) }})
                            </option>
                        </select>
                    </div>

                    <div class="order-2 space-y-1">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Omschrijving totaal</label>
                        <input v-model="declarationForm.description_total" type="text" placeholder="Korte samenvatting van de declaratie" class="w-full rounded border border-app-border bg-white px-3 py-2 text-black" required />
                    </div>

                    <div class="order-4 space-y-1">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">IBAN</label>
                        <input v-model="declarationForm.iban" type="text" placeholder="NL.." class="w-full rounded border border-app-border bg-white px-3 py-2 text-black" required />
                    </div>

                    <div class="order-5 space-y-1">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Rekeninghouder</label>
                        <input v-model="declarationForm.account_name" type="text" placeholder="Naam rekeninghouder" class="w-full rounded border border-app-border bg-white px-3 py-2 text-black" required />
                    </div>

                    <div class="order-6 space-y-1">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Datum aankoop</label>
                        <input v-model="declarationForm.declared_at" type="date" class="w-full rounded border border-app-border bg-white px-3 py-2 text-black" required />
                    </div>

                    <div class="order-8 space-y-1">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Bedrag (op basis van bonregels)</label>
                        <input v-model="declarationForm.amount" type="number" step="0.01" min="0.01" class="w-full rounded border border-app-border bg-slate-100 px-3 py-2 text-black" required readonly />
                    </div>

                    <div class="order-6 space-y-2 sm:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Bonnetje uploaden</label>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" class="rounded bg-brand-blue px-3 py-2 text-sm font-semibold text-white hover:bg-brand-blue-dark disabled:cursor-not-allowed disabled:opacity-60" :disabled="ocrLoading" @click="openGalleryUpload">
                                Upload uit galerij / bestanden
                            </button>
                            <button type="button" class="rounded bg-slate-800 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-900 disabled:cursor-not-allowed disabled:opacity-60" :disabled="ocrLoading" @click="openCameraUpload">
                                Gebruik camera (OCR)
                            </button>
                        </div>
                        <input ref="galleryReceiptInput" type="file" accept="image/*,.pdf,.heic,.heif,image/heic,image/heif" class="hidden" @change="onReceiptChange" />
                        <input ref="cameraReceiptInput" type="file" accept="image/*,.heic,.heif,image/heic,image/heif" capture="environment" class="hidden" @change="onReceiptChange" />
                    </div>

                    <div class="order-7 space-y-2 sm:col-span-2">
                        <div class="flex items-center justify-between gap-2">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Bonregels</label>
                            <button type="button" class="rounded border border-app-border bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100" @click="addReceiptRow">
                                Regel toevoegen
                            </button>
                        </div>
                        <div class="overflow-x-auto rounded-lg border border-app-border">
                            <table class="min-w-full divide-y divide-app-border text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-2 py-2 text-left font-semibold text-slate-700">Naam</th>
                                        <th class="px-2 py-2 text-left font-semibold text-slate-700">Aantal</th>
                                        <th class="px-2 py-2 text-left font-semibold text-slate-700">Bedrag</th>
                                        <th class="px-2 py-2 text-left font-semibold text-slate-700">Btw%</th>
                                        <th class="px-2 py-2 text-left font-semibold text-slate-700">Actie</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-app-border bg-white">
                                    <tr v-for="(row, index) in receiptRows" :key="`receipt-row-${index}`">
                                        <td class="px-2 py-2"><input v-model="row.name" type="text" class="w-full rounded border border-app-border px-2 py-1.5 text-black" /></td>
                                        <td class="px-2 py-2"><input v-model="row.quantity" type="number" min="0" step="0.01" class="w-24 rounded border border-app-border px-2 py-1.5 text-black" /></td>
                                        <td class="px-2 py-2"><input v-model="row.amount" type="number" min="0" step="0.01" class="w-28 rounded border border-app-border px-2 py-1.5 text-black" /></td>
                                        <td class="px-2 py-2"><input v-model="row.vat" type="number" min="0" step="0.01" class="w-20 rounded border border-app-border px-2 py-1.5 text-black" /></td>
                                        <td class="px-2 py-2">
                                            <button type="button" class="rounded border border-red-200 bg-red-50 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-100" @click="removeReceiptRow(index)">
                                                Verwijder
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-app-border pt-3">
                    <button type="button" class="rounded bg-slate-200 px-3 py-2 text-sm text-slate-800 hover:bg-slate-300 disabled:cursor-not-allowed disabled:opacity-60" :disabled="ocrLoading || !declarationForm.receipt_file" @click="runOcrAssist">
                        {{ ocrLoading ? 'OCR bezig...' : 'OCR voorstel invullen' }}
                    </button>
                    <button type="submit" class="rounded bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60" :disabled="declarationForm.processing || !hasActivePots">
                        {{ declarationForm.processing ? 'Declaratie wordt verstuurd...' : 'Declaratie indienen' }}
                    </button>
                </div>
            </form>

            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                <h4 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Declaraties overzicht</h4>
                <div class="mt-3 space-y-2">
                    <div v-for="row in props.declarations" :key="`declaration-${row.id}`" class="rounded-lg border border-app-border bg-white p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-black">{{ row.pot_name || 'Onbekend potje' }} - EUR {{ row.amount }}</p>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700">{{ row.status }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-600">{{ row.description_total }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ row.created_by_name }} | {{ row.declared_at }}</p>
                        <div v-if="row.can_review" class="mt-2 flex gap-2">
                            <button type="button" class="rounded bg-emerald-700 px-3 py-1.5 text-xs text-white hover:bg-emerald-800" @click="approve(row.id)">Goedkeuren</button>
                            <button type="button" class="rounded bg-red-600 px-3 py-1.5 text-xs text-white hover:bg-red-700" @click="reject(row.id)">Afkeuren</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

