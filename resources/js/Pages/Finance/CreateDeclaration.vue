<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { moneyDisplayValue, sanitizeMoneyInput } from '@/utils/money';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon } from '@heroicons/vue/24/outline';
import { useSaveRedirect } from '@/utils/saveForm';

const fieldClass =
    'rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted-dark';

const props = defineProps({
    pots: { type: Array, default: () => [] },
});
const { applySaveRedirect, saveFormOptions } = useSaveRedirect();

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
    form
        .transform((data) => applySaveRedirect(data))
        .post(route('finance.declarations.store'), saveFormOptions({ forceFormData: true }));
}
</script>

<template>
    <Head :title="`${speltakLabel} - Declaratie toevoegen`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Declaratie toevoegen</h2>
                <Link :href="route('finance.declarations.index')" class="btn-action-back" title="Terug" aria-label="Terug">
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <form
            class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 text-app-ink shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark dark:text-app-ink-dark"
            @submit.prevent="submit"
        >
            <div class="grid gap-3 sm:grid-cols-2">
                <select
                    v-model="form.pot_id"
                    :class="`${fieldClass} dark:[&>option]:bg-app-canvas-dark dark:[&>option]:text-app-ink-dark`"
                    required
                >
                    <option value="" disabled>Kies potje</option>
                    <option v-for="pot in props.pots" :key="pot.id" :value="pot.id">{{ pot.name }}</option>
                </select>
                <input v-model="form.iban" type="text" placeholder="IBAN" :class="fieldClass" required />
                <input v-model="form.account_name" type="text" placeholder="Rekeninghouder" :class="fieldClass" required />
                <input v-model="form.declared_at" type="date" :class="fieldClass" required />
                <input
                    :value="moneyDisplayValue(form.amount, { fallback: '0,00' })"
                    type="text"
                    inputmode="decimal"
                    placeholder="Bedrag"
                    :class="fieldClass"
                    required
                    @input="onAmountInput"
                />
                <input type="file" accept="image/*,.pdf,.heic,.heif" :class="fieldClass" required @change="onReceiptChange" />
                <input v-model="form.description_total" type="text" placeholder="Omschrijving totaal" :class="[fieldClass, 'sm:col-span-2']" required />
                <textarea v-model="form.description_lines" rows="5" placeholder="Bonregels" :class="[fieldClass, 'sm:col-span-2']" required />
            </div>
            <button type="submit" class="rounded bg-emerald-700 px-4 py-2 text-white">Opslaan</button>
        </form>
    </AuthenticatedLayout>
</template>

