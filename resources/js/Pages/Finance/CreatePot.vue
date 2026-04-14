<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { moneyDisplayValue, sanitizeMoneyInput } from '@/utils/money';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

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
    name: '',
    starting_amount: '',
    active: true,
});

function onStartingAmountInput(event) {
    form.starting_amount = sanitizeMoneyInput(event?.target?.value ?? form.starting_amount, { allowEmpty: false });
}

function submit() {
    form.post(route('finance.pots.store'));
}
</script>

<template>
    <Head :title="`${speltakLabel} - Potje toevoegen`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Potje toevoegen</h2>
                <Link :href="route('finance.pots.index')" class="btn-action-back">Terug</Link>
            </div>
        </template>

        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5" @submit.prevent="submit">
            <div class="grid gap-3 sm:grid-cols-2">
                <input v-model="form.name" type="text" placeholder="Naam potje" class="rounded border border-app-border px-3 py-2" required />
                <input :value="moneyDisplayValue(form.starting_amount, { fallback: '0,00' })" type="text" inputmode="decimal" placeholder="Startbudget" class="rounded border border-app-border px-3 py-2" required @input="onStartingAmountInput" />
                <label class="inline-flex items-center gap-2 rounded border border-app-border px-3 py-2 text-sm">
                    <input v-model="form.active" type="checkbox" />
                    Direct actief
                </label>
            </div>
            <button type="submit" class="rounded bg-emerald-700 px-4 py-2 text-white">Opslaan</button>
        </form>
    </AuthenticatedLayout>
</template>

