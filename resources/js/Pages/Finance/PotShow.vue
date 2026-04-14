<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { moneyDisplayValue, sanitizeMoneyInput } from '@/utils/money';
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
                <Link :href="route('finance.pots.index')" class="btn-action-back">
                    <ArrowUturnLeftIcon class="h-4 w-4" />
                </Link>
            </div>
        </template>

        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 dark:border-brand-blue/30 dark:bg-app-panel-dark" @submit.prevent="submit">
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="space-y-1">
                    <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Naam potje</label>
                    <input v-model="form.name" type="text" class="w-full rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" required />
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Startbudget</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-app-muted dark:text-app-muted-dark">€</span>
                        <input
                            :value="moneyDisplayValue(form.starting_amount, { fallback: '0,00' })"
                            type="text"
                            inputmode="decimal"
                            class="w-full rounded border border-app-border bg-white py-2 pl-8 pr-3 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                            required
                            @input="onStartingAmountInput"
                        />
                    </div>
                </div>
                <div class="sm:col-span-2 flex items-center gap-3">
                    <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Actief</label>
                    <button
                        type="button"
                        class="relative inline-flex h-7 w-14 items-center rounded-full border transition"
                        :class="form.active ? 'border-brand-blue bg-brand-blue' : 'border-app-border bg-slate-300 dark:border-app-border-dark dark:bg-slate-700'"
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
            <button type="submit" class="btn-action-save" :disabled="form.processing" title="Opslaan" aria-label="Opslaan">
                <DocumentCheckIcon class="h-5 w-5" />
            </button>
        </form>
    </AuthenticatedLayout>
</template>

