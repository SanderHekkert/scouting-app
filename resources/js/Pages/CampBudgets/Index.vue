<script setup>
import { computed, ref } from 'vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { formatMoney } from '@/utils/money';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, BellAlertIcon, ChatBubbleLeftEllipsisIcon, CheckIcon, ChevronDownIcon, DocumentDuplicateIcon, PencilSquareIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

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
const isBestuurSection = computed(() => page.props.auth?.active_section === 'bestuur');
const deleteModalItem = ref(null);
const feedbackModalItem = ref(null);
const feedbackNote = ref('');
const openYears = ref({});

const groupedItems = computed(() => {
    const grouped = new Map();
    for (const item of props.items || []) {
        const year = String(item?.camp_year ?? 'Onbekend');
        if (!grouped.has(year)) {
            grouped.set(year, []);
        }
        grouped.get(year).push(item);
    }

    return Array.from(grouped.entries())
        .sort((a, b) => Number.parseInt(b[0], 10) - Number.parseInt(a[0], 10))
        .map(([year, items]) => ({ year, items }));
});

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
    deleteModalItem.value = item;
}

function closeDeleteModal() {
    deleteModalItem.value = null;
}

function confirmDeleteItem() {
    const item = deleteModalItem.value;
    if (!item?.id) return;
    router.delete(route('camp-budgets.destroy', item.id), { preserveScroll: true });
    closeDeleteModal();
}

function approveItem(item) {
    router.patch(route('camp-budgets.approve', item.id), {}, { preserveScroll: true });
}

function rejectItem(item) {
    if (!item?.id) return;
    feedbackModalItem.value = item;
    feedbackNote.value = String(item.review_note || '');
}

function closeFeedbackModal() {
    feedbackModalItem.value = null;
    feedbackNote.value = '';
}

function submitFeedback() {
    const item = feedbackModalItem.value;
    if (!item?.id) return;
    const review_note = feedbackNote.value.trim();
    if (!review_note) return;
    router.patch(route('camp-budgets.reject', item.id), { review_note }, {
        preserveScroll: true,
        onSuccess: () => closeFeedbackModal(),
    });
}

function statusLabel(status) {
    if (status === 'draft') return 'Concept';
    if (status === 'submitted') return 'Wacht op goedkeuring';
    if (status === 'approved') return 'Goedgekeurd';
    if (status === 'needs_changes') return 'Aanpassing(en) nodig';
    return status;
}

function statusClass(status) {
    if (status === 'draft') return 'bg-slate-100 text-slate-700';
    if (status === 'approved') return 'bg-emerald-100 text-emerald-800';
    if (status === 'needs_changes') return 'bg-amber-100 text-amber-800';
    return 'bg-sky-100 text-sky-800';
}

function isYearOpen(year) {
    return openYears.value[year] !== false;
}

function toggleYear(year) {
    openYears.value[year] = !isYearOpen(year);
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
                <div v-for="group in groupedItems" :key="`budget-year-${group.year}`" class="space-y-2">
                    <button type="button" class="flex w-full items-center justify-between rounded-lg border border-app-border bg-white px-3 py-2 text-left dark:border-app-border-dark dark:bg-app-canvas-dark" @click="toggleYear(group.year)">
                        <span class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">
                            {{ group.year }} ({{ group.items.length }})
                        </span>
                        <ChevronDownIcon class="h-4 w-4 text-slate-500 transition-transform dark:text-slate-300" :class="isYearOpen(group.year) ? 'rotate-180' : ''" />
                    </button>

                    <div v-if="isYearOpen(group.year)" class="space-y-2">
                        <div v-for="item in group.items" :key="`budget-${item.id}`" class="rounded-lg border border-app-border bg-white p-3 dark:bg-app-canvas-dark">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-app-ink dark:text-app-ink-dark">{{ item.camp_year }} - {{ item.title }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-300">
                                        {{ sectionLabels[item.section] || item.section }} | Door {{ item.created_by_name || 'Onbekend' }} | Gewijzigd {{ formattedUpdatedAt(item.updated_at) }}
                                        | Laatst gewijzigd door {{ item.updated_by_name || 'Onbekend' }}
                                    </p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-800 dark:border-emerald-500/40 dark:bg-emerald-500/15 dark:text-emerald-300">
                                            Bijdragen € {{ formatMoney(item.totals?.income || 0) }}
                                        </span>
                                        <span class="inline-flex items-center rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-800 dark:border-rose-500/40 dark:bg-rose-500/15 dark:text-rose-300">
                                            Uitgaven € {{ formatMoney(item.totals?.expenses || 0) }}
                                        </span>
                                        <span class="inline-flex items-center rounded-md border border-brand-blue/35 bg-brand-blue/10 px-2 py-1 text-xs font-semibold text-brand-blue-dark dark:border-brand-blue/40 dark:bg-brand-blue/20 dark:text-brand-blue-light">
                                            Verschil € {{ formatMoney(item.totals?.difference || 0) }}
                                        </span>
                                    </div>
                                    <div v-if="item.review_note" class="mt-2 rounded-md border border-amber-200 bg-amber-50 px-2.5 py-2 text-xs text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/15 dark:text-amber-200">
                                        <p class="font-semibold">Notitie bestuur:</p>
                                        <p class="mt-0.5 whitespace-pre-line">{{ item.review_note }}</p>
                                    </div>
                                    <details v-if="item.review_notes?.length" class="mt-2 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-2 text-xs text-slate-800 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100">
                                        <summary class="cursor-pointer font-semibold select-none">
                                            Eerdere feedback ({{ item.review_notes.length }})
                                        </summary>
                                        <div class="mt-2 space-y-2">
                                            <div v-for="(entry, noteIdx) in item.review_notes" :key="`review-note-${item.id}-${noteIdx}`" class="rounded border border-slate-200 bg-white px-2 py-1.5 dark:border-slate-600 dark:bg-slate-900/70">
                                                <p class="text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                                                    {{ entry.user_name || 'Onbekend' }} - {{ formattedUpdatedAt(entry.at) }}
                                                </p>
                                                <p class="mt-0.5 whitespace-pre-line text-slate-800 dark:text-slate-100">{{ entry.note }}</p>
                                            </div>
                                        </div>
                                    </details>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <span :class="['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs', statusClass(item.status)]">
                                        {{ statusLabel(item.status) }}
                                        <BellAlertIcon class="h-3.5 w-3.5" />
                                    </span>
                                    <Link v-if="canUpdate" :href="route('camp-budgets.show', item.id)" class="btn-action-save" title="Bewerken" aria-label="Bewerken">
                                        <PencilSquareIcon class="h-5 w-5" />
                                    </Link>
                                    <a v-if="canUpdate" :href="route('camp-budgets.pdf.download', item.id)" class="btn-action-save" title="PDF downloaden" aria-label="PDF downloaden">
                                        <ArrowDownTrayIcon class="h-5 w-5" />
                                    </a>
                                    <button v-if="canCreate && !isBestuurSection" type="button" class="btn-action-save" title="Kopie maken" aria-label="Kopie maken" @click="copyItem(item)">
                                        <DocumentDuplicateIcon class="h-5 w-5" />
                                    </button>
                                    <button v-if="canUpdate && !isBestuurSection" type="button" class="btn-action-delete" title="Verwijderen" aria-label="Verwijderen" @click="deleteItem(item)">
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                    <button v-if="item.can_review" type="button" class="btn-action-save" title="Goedkeuren" aria-label="Goedkeuren" @click="approveItem(item)">
                                        <CheckIcon class="h-5 w-5" />
                                    </button>
                                    <button v-if="item.can_review" type="button" class="btn-action-save" title="Feedback achterlaten (Aanpassing(en) nodig)" aria-label="Feedback achterlaten" @click="rejectItem(item)">
                                        <ChatBubbleLeftEllipsisIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <AppConfirmModal
        :show="!!deleteModalItem"
        title="Begroting verwijderen?"
        :message="deleteModalItem ? `Weet je zeker dat je begroting '${deleteModalItem.title}' wilt verwijderen?` : ''"
        confirm-text="Ja, verwijderen"
        cancel-text="Annuleren"
        @close="closeDeleteModal"
        @confirm="confirmDeleteItem"
    />

    <Modal :show="!!feedbackModalItem" max-width="lg" @close="closeFeedbackModal">
        <div class="rainbow-animate h-1 w-full bg-gradient-to-r from-brand-red via-brand-yellow to-brand-blue" aria-hidden="true" />
        <div class="space-y-4 p-6 sm:p-7">
            <div>
                <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Feedback achterlaten</h3>
                <p class="mt-1 text-sm text-app-muted dark:text-app-muted-dark">
                    Laat een notitie achter voor deze begroting zodat duidelijk is wat aangepast moet worden.
                </p>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-app-muted-dark">Notitie</label>
                <textarea
                    v-model="feedbackNote"
                    rows="5"
                    class="w-full rounded-md border border-app-border bg-white px-3 py-2 text-sm text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    placeholder="Schrijf hier wat er aangepast moet worden..."
                />
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <button type="button" class="inline-flex min-h-10 items-center rounded-md border border-app-border bg-app-panel px-4 py-2 text-sm font-medium text-app-ink transition hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15" @click="closeFeedbackModal">
                    Annuleren
                </button>
                <button type="button" class="inline-flex min-h-10 items-center rounded-md border border-amber-600 bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-60" :disabled="!feedbackNote.trim()" @click="submitFeedback">
                    Feedback opslaan
                </button>
            </div>
        </div>
    </Modal>
</template>

