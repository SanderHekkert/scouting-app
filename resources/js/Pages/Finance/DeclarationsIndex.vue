<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    declarations: { type: Array, default: () => [] },
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

function approve(id) {
    router.patch(route('finance.declarations.approve', id), {}, { preserveScroll: true });
}

function reject(id) {
    const review_note = prompt('Reden van afkeuren (optioneel):') || '';
    router.patch(route('finance.declarations.reject', id), { review_note }, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`${speltakLabel} - Declaraties`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Declaraties</h2>
                <Link
                    :href="route('finance.declarations.create')"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-700 text-white shadow-sm transition hover:bg-emerald-800"
                    title="Nieuwe declaratie toevoegen"
                    aria-label="Nieuwe declaratie toevoegen"
                >
                    <PlusIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <div class="space-y-4">
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

