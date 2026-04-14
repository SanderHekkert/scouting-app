<script setup>
import { computed, ref } from 'vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import Modal from '@/Components/Modal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, BellAlertIcon, ChatBubbleLeftEllipsisIcon, CheckIcon, DocumentDuplicateIcon, PencilSquareIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    items: { type: Array, default: () => [] },
    canReview: { type: Boolean, default: false },
});

const page = usePage();
const perms = computed(() => page.props.auth?.permissions?.camp_playbooks ?? {});
const canCreate = computed(() => !!perms.value.create);
const canUpdate = computed(() => !!perms.value.update);
const isBestuurSection = computed(() => page.props.auth?.active_section === 'bestuur');

const sectionLabels = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const speltakLabel = computed(() => sectionLabels[page.props.auth?.active_section] || 'Dolfijnen');
const deleteModalItem = ref(null);
const feedbackModalItem = ref(null);
const feedbackNote = ref('');

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
    router.post(route('camp-playbooks.copy', item.id), {}, { preserveScroll: true });
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
    router.delete(route('camp-playbooks.destroy', item.id), { preserveScroll: true });
    closeDeleteModal();
}

function approveItem(item) {
    router.patch(route('camp-playbooks.approve', item.id), {}, { preserveScroll: true });
}

function rejectItem(item) {
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
    router.patch(route('camp-playbooks.reject', item.id), { review_note }, {
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

function campTypeLabel(value) {
    return String(value) === 'clubhuis' ? 'Clubhuis' : 'Fram';
}
</script>

<template>
    <Head :title="`${speltakLabel} - Draaiboek`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Draaiboek</h2>
                <Link
                    v-if="canCreate"
                    :href="route('camp-playbooks.create')"
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
                    Nog geen draaiboek toegevoegd.
                </div>
                <div v-for="item in props.items" :key="`playbook-${item.id}`" class="rounded-lg border border-app-border bg-white p-3 dark:bg-app-canvas-dark">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex items-start gap-3">
                            <div class="h-24 w-24 shrink-0 overflow-hidden rounded-md border border-app-border bg-slate-100 dark:border-app-border-dark dark:bg-slate-800">
                                <img
                                    v-if="item.cover_photo_url"
                                    :src="item.cover_photo_url"
                                    alt="Cover"
                                    class="h-full w-full object-cover"
                                />
                                <div v-else class="flex h-full w-full items-center justify-center text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">
                                    Geen cover
                                </div>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">{{ item.camp_year }} - {{ item.title }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-300">
                                    {{ sectionLabels[item.section] || item.section }} | Door {{ item.created_by_name || 'Onbekend' }} | Gewijzigd {{ formattedUpdatedAt(item.updated_at) }}
                                    | Laatst gewijzigd door {{ item.updated_by_name || 'Onbekend' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-600 dark:text-slate-200">
                                    Kamptype: {{ campTypeLabel(item.camp_location) }}
                                    <span v-if="item.camp_place"> | Plaats: {{ item.camp_place }}</span>
                                    <span v-if="item.camp_dates"> | Datum: {{ item.camp_dates }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="min-w-0">
                            <div v-if="item.review_note" class="mt-2 rounded-md border border-amber-200 bg-amber-50 px-2.5 py-2 text-xs text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/15 dark:text-amber-200">
                                <p class="font-semibold">Notitie bestuur:</p>
                                <p class="mt-0.5 whitespace-pre-line">{{ item.review_note }}</p>
                            </div>
                            <details v-if="item.review_notes?.length" class="mt-2 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-2 text-xs text-slate-800 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100">
                                <summary class="cursor-pointer font-semibold select-none">
                                    Eerdere feedback ({{ item.review_notes.length }})
                                </summary>
                                <div class="mt-2 space-y-2">
                                    <div v-for="(entry, noteIdx) in item.review_notes" :key="`playbook-review-note-${item.id}-${noteIdx}`" class="rounded border border-slate-200 bg-white px-2 py-1.5 dark:border-slate-600 dark:bg-slate-900/70">
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
                            <Link v-if="canUpdate" :href="route('camp-playbooks.show', item.id)" class="btn-action-save" title="Bewerken" aria-label="Bewerken">
                                <PencilSquareIcon class="h-5 w-5" />
                            </Link>
                            <a v-if="canUpdate" :href="route('camp-playbooks.pdf.download', item.id)" class="btn-action-save" title="PDF downloaden" aria-label="PDF downloaden">
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
    </AuthenticatedLayout>

    <AppConfirmModal
        :show="!!deleteModalItem"
        title="Draaiboek verwijderen?"
        :message="deleteModalItem ? `Weet je zeker dat je draaiboek '${deleteModalItem.title}' wilt verwijderen?` : ''"
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
                    Laat een notitie achter voor dit draaiboek zodat duidelijk is wat aangepast moet worden.
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

