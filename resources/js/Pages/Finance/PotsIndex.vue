<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { BanknotesIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    pots: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
    canCreatePots: { type: Boolean, default: false },
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
const speltakLabel = computed(() => sectionLabelMap[page.props.auth?.active_section] || 'Dolfijnen');

function formatCurrency(value) {
    const amount = Number(value || 0);
    return new Intl.NumberFormat('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);
}
</script>

<template>
    <Head :title="`${speltakLabel} - Potjes`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Potjes</h2>
                <Link
                    v-if="canCreatePots"
                    :href="route('finance.pots.create')"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-700 text-white shadow-sm transition hover:bg-emerald-800"
                    title="Nieuw potje toevoegen"
                    aria-label="Nieuw potje toevoegen"
                >
                    <PlusIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <div class="space-y-4">
            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                <h4 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Potjes overzicht</h4>

                <div class="mt-3 space-y-2">
                    <p v-if="!props.pots.length" class="rounded-lg border border-dashed border-app-border bg-white px-3 py-3 text-sm text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-muted-dark">
                        Er zijn nog geen potjes voor deze speltak.
                    </p>
                    <div v-for="pot in props.pots" :key="`pot-${pot.id}`" class="rounded-xl border border-app-border bg-white p-4 shadow-sm dark:border-app-border-dark dark:bg-app-canvas-dark">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">{{ pot.name }}</p>
                                <p class="mt-1 text-xs text-app-muted dark:text-app-muted-dark">Startbudget: € {{ formatCurrency(pot.starting_amount) }}</p>
                            </div>
                            <BanknotesIcon class="h-8 w-8 text-emerald-700" />
                        </div>
                        <p class="mt-4 text-4xl font-bold leading-none text-app-ink dark:text-app-ink-dark">€ {{ formatCurrency(pot.current_amount) }}</p>
                        <p class="mt-2 text-xs" :class="pot.active ? 'text-emerald-700 dark:text-emerald-400' : 'text-app-muted dark:text-app-muted-dark'">
                            {{ pot.active ? 'Actief potje' : 'Inactief potje' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

