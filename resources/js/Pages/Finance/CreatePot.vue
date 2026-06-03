<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { moneyDisplayValue, sanitizeMoneyInput } from '@/utils/money';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon } from '@heroicons/vue/24/outline';

const fieldClass =
    'rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted-dark';

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

function setActiveStatus(isActive) {
    form.active = !!isActive;
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
                <Link :href="route('finance.pots.index')" class="btn-action-back" title="Terug" aria-label="Terug">
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <form
            class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 text-app-ink shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark dark:text-app-ink-dark"
            @submit.prevent="submit"
        >
            <div class="grid gap-3 sm:grid-cols-2">
                <input v-model="form.name" type="text" placeholder="Naam potje" :class="fieldClass" required />
                <input
                    :value="moneyDisplayValue(form.starting_amount, { fallback: '0,00' })"
                    type="text"
                    inputmode="decimal"
                    placeholder="Startbudget"
                    :class="fieldClass"
                    required
                    @input="onStartingAmountInput"
                />
                <div class="sm:col-span-2 flex items-center gap-3">
                    <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Actief</label>
                    <button
                        type="button"
                        class="relative inline-flex h-7 w-14 items-center rounded-full border transition"
                        :class="form.active ? 'border-brand-blue bg-brand-blue' : 'border-app-border bg-slate-300'"
                        :aria-pressed="form.active"
                        aria-label="Potje actief"
                        @click="setActiveStatus(!form.active)"
                    >
                        <span class="sr-only">Actief</span>
                        <span
                            class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition"
                            :class="form.active ? 'translate-x-8' : 'translate-x-1'"
                        />
                    </button>
                </div>
            </div>
            <button type="submit" class="rounded bg-emerald-700 px-4 py-2 text-white">Opslaan</button>
        </form>
    </AuthenticatedLayout>
</template>

