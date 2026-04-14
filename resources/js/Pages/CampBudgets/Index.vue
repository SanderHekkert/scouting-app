<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatMoney } from '@/utils/money';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { DocumentDuplicateIcon, PencilSquareIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    items: { type: Array, default: () => [] },
    canReview: { type: Boolean, default: false },
});

const page = usePage();
const perms = computed(() => page.props.auth?.permissions?.camp_budgets ?? {});
const canCreate = computed(() => !!perms.value.create);
const canUpdate = computed(() => !!perms.value.update);

const sectionLabels = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const speltakLabel = computed(() => sectionLabels[page.props.auth?.active_section] || 'Dolfijnen');

function formattedUpdatedAt(value) {
    if (!value) return 'Onbekend';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'Onbekend';
    return new Intl.DateTimeFormat('nl-NL', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

function copyItem(item) {
    if (!canCreate.value) return;
    router.post(route('camp-budgets.copy', item.id), {}, { preserveScroll: true });
}

function deleteItem(item) {
    if (!canUpdate.value) return;
    if (!confirm(`Begroting "${item.title}" verwijderen?`)) return;
    router.delete(route('camp-budgets.destroy', item.id), { preserveScroll: true });
}

function approveItem(item) {
    router.patch(route('camp-budgets.approve', item.id), {}, { preserveScroll: true });
}

function rejectItem(item) {
    const review_note = prompt('Wat moet aangepast worden?') || '';
    if (!review_note.trim()) return;
    router.patch(route('camp-budgets.reject', item.id), { review_note }, { preserveScroll: true });
}

function statusLabel(status) {
    if (status === 'draft') return 'Concept';
    if (status === 'submitted') return 'Wacht op goedkeuring';
    if (status === 'approved') return 'Goedgekeurd';
    if (status === 'needs_changes') return 'Aanpassen nodig';
    return status;
}

function statusClass(status) {
    if (status === 'draft') return 'bg-slate-100 text-slate-700';
    if (status === 'approved') return 'bg-emerald-100 text-emerald-800';
    if (status === 'needs_changes') return 'bg-amber-100 text-amber-800';
    return 'bg-sky-100 text-sky-800';
}
</script>

<template>
    <Head :title="`${speltakLabel} - Begroting`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Begroting</h2>
                <Link
                    v-if="canCreate"
                    :href="route('camp-budgets.create')"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-700 text-white shadow-sm transition hover:bg-emerald-800"
                    title="Toevoegen"
                    aria-label="Toevoegen"
                >
                    <PlusIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <div class="space-y-4">
            <div class="surface-brand-top space-y-3 rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                <div v-if="!props.items.length" class="py-6 text-center text-sm text-app-ink dark:text-app-ink-dark">
                    Nog geen begroting toegevoegd.
                </div>
                <div v-for="item in props.items" :key="`budget-${item.id}`" class="rounded-lg border border-app-border bg-white p-3 dark:bg-app-canvas-dark">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-app-ink dark:text-app-ink-dark">{{ item.camp_year }} - {{ item.title }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-300">
                                {{ sectionLabels[item.section] || item.section }} | Door {{ item.created_by_name || 'Onbekend' }} | Gewijzigd {{ formattedUpdatedAt(item.updated_at) }}
                            </p>
                            <p class="mt-1 text-xs text-app-ink dark:text-app-ink-dark">
                                Bijdragen: € {{ formatMoney(item.totals?.income || 0) }}
                                | Uitgaven: € {{ formatMoney(item.totals?.expenses || 0) }}
                                | Verschil: € {{ formatMoney(item.totals?.difference || 0) }}
                            </p>
                            <p v-if="item.review_note" class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                                Opmerking bestuur: {{ item.review_note }}
                            </p>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <span :class="['rounded-full px-2 py-0.5 text-xs', statusClass(item.status)]">{{ statusLabel(item.status) }}</span>
                            <Link v-if="canUpdate" :href="route('camp-budgets.show', item.id)" class="btn-action-save" title="Bewerken" aria-label="Bewerken">
                                <PencilSquareIcon class="h-5 w-5" />
                            </Link>
                            <button v-if="canCreate" type="button" class="btn-action-save" title="Kopie maken" aria-label="Kopie maken" @click="copyItem(item)">
                                <DocumentDuplicateIcon class="h-5 w-5" />
                            </button>
                            <button v-if="canUpdate" type="button" class="btn-action-delete" title="Verwijderen" aria-label="Verwijderen" @click="deleteItem(item)">
                                <TrashIcon class="h-5 w-5" />
                            </button>
                            <button v-if="item.can_review" type="button" class="rounded bg-emerald-700 px-3 py-1.5 text-xs text-white hover:bg-emerald-800" @click="approveItem(item)">Goedkeuren</button>
                            <button v-if="item.can_review" type="button" class="rounded bg-amber-600 px-3 py-1.5 text-xs text-white hover:bg-amber-700" @click="rejectItem(item)">Aanpassen nodig</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

