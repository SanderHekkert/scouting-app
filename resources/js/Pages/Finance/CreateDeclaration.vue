<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { moneyDisplayValue, sanitizeMoneyInput } from '@/utils/money';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    pots: { type: Array, default: () => [] },
});

const page = usePage();
const sectionLabelMap = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const speltakLabel = sectionLabelMap[page.props.auth?.active_section] || 'Dolfijnen';

const form = useForm({
    pot_id: '',
    amount: '',
    iban: '',
    account_name: '',
    description_total: '',
    description_lines: '',
    declared_at: '',
    receipt_file: null,
});

function onReceiptChange(event) {
    form.receipt_file = event?.target?.files?.[0] || null;
}

function onAmountInput(event) {
    form.amount = sanitizeMoneyInput(event?.target?.value ?? form.amount, { allowEmpty: false });
}

function submit() {
    form.post(route('finance.declarations.store'), { forceFormData: true });
}
</script>

<template>
    <Head :title="`${speltakLabel} - Declaratie toevoegen`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Declaratie toevoegen</h2>
                <Link :href="route('finance.declarations.index')" class="btn-action-back">Terug</Link>
            </div>
        </template>

        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5" @submit.prevent="submit">
            <div class="grid gap-3 sm:grid-cols-2">
                <select v-model="form.pot_id" class="rounded border border-app-border px-3 py-2" required>
                    <option value="" disabled>Kies potje</option>
                    <option v-for="pot in props.pots" :key="pot.id" :value="pot.id">{{ pot.name }}</option>
                </select>
                <input v-model="form.iban" type="text" placeholder="IBAN" class="rounded border border-app-border px-3 py-2" required />
                <input v-model="form.account_name" type="text" placeholder="Rekeninghouder" class="rounded border border-app-border px-3 py-2" required />
                <input v-model="form.declared_at" type="date" class="rounded border border-app-border px-3 py-2" required />
                <input :value="moneyDisplayValue(form.amount, { fallback: '0,00' })" type="text" inputmode="decimal" placeholder="Bedrag" class="rounded border border-app-border px-3 py-2" required @input="onAmountInput" />
                <input type="file" accept="image/*,.pdf,.heic,.heif" class="rounded border border-app-border px-3 py-2" required @change="onReceiptChange" />
                <input v-model="form.description_total" type="text" placeholder="Omschrijving totaal" class="rounded border border-app-border px-3 py-2 sm:col-span-2" required />
                <textarea v-model="form.description_lines" rows="5" placeholder="Bonregels" class="rounded border border-app-border px-3 py-2 sm:col-span-2" required />
            </div>
            <button type="submit" class="rounded bg-emerald-700 px-4 py-2 text-white">Opslaan</button>
        </form>
    </AuthenticatedLayout>
</template>

