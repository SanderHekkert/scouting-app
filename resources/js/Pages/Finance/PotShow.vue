<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon, DocumentCheckIcon } from '@heroicons/vue/24/outline';

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
    const cleaned = String(event?.target?.value ?? '')
        .replace(',', '.')
        .replace(/[^0-9.]/g, '');
    const [intPartRaw, ...decimalParts] = cleaned.split('.');
    const intPart = intPartRaw || '0';
    const decimalPart = decimalParts.join('').slice(0, 2);
    form.starting_amount = decimalPart.length > 0 ? `${intPart}.${decimalPart}` : intPart;
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
                <h2 class="text-xl font-semibold text-black">{{ speltakLabel }} - Potje toevoegen</h2>
                <Link :href="route('finance.pots.index')" class="btn-action-back">
                    <ArrowUturnLeftIcon class="h-4 w-4" />
                </Link>
            </div>
        </template>

        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5" @submit.prevent="submit">
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="space-y-1">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Naam potje</label>
                    <input v-model="form.name" type="text" class="w-full rounded border border-app-border bg-white px-3 py-2 text-black" required />
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Startbudget</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">€</span>
                        <input
                            :value="form.starting_amount"
                            type="text"
                            inputmode="decimal"
                            class="w-full rounded border border-app-border bg-white py-2 pl-8 pr-3 text-black"
                            required
                            @input="onStartingAmountInput"
                        />
                    </div>
                </div>
                <div class="space-y-1 sm:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Status</label>
                    <label class="inline-flex items-center gap-2 rounded border border-app-border bg-white px-3 py-2 text-sm text-black">
                        <input v-model="form.active" type="checkbox" />
                        Direct actief
                    </label>
                </div>
            </div>
            <button type="submit" class="btn-action-save" :disabled="form.processing" title="Opslaan" aria-label="Opslaan">
                <DocumentCheckIcon class="h-5 w-5" />
            </button>
        </form>
    </AuthenticatedLayout>
</template>

