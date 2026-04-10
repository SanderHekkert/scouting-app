<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ArrowTopRightOnSquareIcon, DocumentCheckIcon, PencilSquareIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    notes: { type: Array, default: () => [] },
    canCreateCrossSection: { type: Boolean, default: false },
    targetSections: { type: Array, default: () => [] },
});
const page = usePage();
const notePerms = computed(() => page.props.auth?.permissions?.info_notes ?? {});
const canCreateNotes = computed(() => !!notePerms.value.create);

const showAddForm = ref(false);

const form = useForm({
    category: '',
    content: '',
    link: '',
    target_section: '',
});

const sectionLabels = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const speltakLabel = computed(() => sectionLabels[page.props.auth?.active_section] || 'Dolfijnen');


function toggleAddForm() {
    if (!canCreateNotes.value) return;
    showAddForm.value = !showAddForm.value;
    if (showAddForm.value) {
        form.reset();
        form.clearErrors();
    }
}

function submitAdd() {
    if (!canCreateNotes.value) return;
    form.post(route('info-notes.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAddForm.value = false;
        },
    });
}

function deleteNote(note) {
    if (!note?.can_delete) return;
    if (!note?.id) return;
    if (!confirm('Deze notitie verwijderen?')) return;
    router.delete(route('info-notes.destroy', note.id), {
        preserveScroll: true,
    });
}

function linkDisplayText(url) {
    if (!url) return '';
    try {
        const u = new URL(url);
        return u.hostname + (u.pathname !== '/' ? u.pathname : '');
    } catch {
        return url;
    }
}

function safeExternalUrl(url) {
    const raw = String(url || '').trim();
    if (!raw) return null;
    try {
        const parsed = new URL(raw, window.location.origin);
        const protocol = parsed.protocol.toLowerCase();
        if (protocol !== 'http:' && protocol !== 'https:') {
            return null;
        }
        return parsed.href;
    } catch {
        return null;
    }
}

function editNote(note) {
    if (!note?.can_update) return;
    if (!note?.id) return;
    router.get(route('info-notes.show', note.id));
}
</script>

<template>
    <Head title="Belangrijke info" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Belangrijke info</h2>
                <div class="flex flex-wrap items-center justify-end gap-2 sm:ms-auto">
                    <button
                        v-if="canCreateNotes"
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-700 text-white shadow-sm transition hover:bg-emerald-800"
                        title="Toevoegen"
                        aria-label="Toevoegen"
                        @click="toggleAddForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </template>
        <div class="space-y-4 text-app-ink dark:text-app-ink-dark">
            <form
                v-if="canCreateNotes"
                v-show="showAddForm"
                class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-5"
                @submit.prevent="submitAdd"
            >
                <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Nieuwe info</h3>
                <div class="grid gap-4 sm:grid-cols-[8rem_1fr] sm:items-start">
                    <label for="add-info-category" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Categorie
                    </label>
                    <input
                        id="add-info-category"
                        v-model="form.category"
                        type="text"
                        placeholder="Bijv. Kamp, Ouder contact"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label v-if="props.canCreateCrossSection" for="add-info-section" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Speltak
                    </label>
                    <select
                        v-if="props.canCreateCrossSection"
                        id="add-info-section"
                        v-model="form.target_section"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    >
                        <option value="">Kies speltak</option>
                        <option v-for="section in props.targetSections" :key="`target-${section}`" :value="section">
                            {{ sectionLabels[section] || section }}
                        </option>
                    </select>

                    <label for="add-info-content" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Inhoud
                    </label>
                    <textarea
                        id="add-info-content"
                        v-model="form.content"
                        rows="4"
                        placeholder="Inhoud…"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-info-link" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Linkje
                    </label>
                    <input
                        id="add-info-link"
                        v-model="form.link"
                        type="text"
                        inputmode="url"
                        autocomplete="off"
                        placeholder="https://… of example.com"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <span class="hidden sm:block" aria-hidden="true" />
                    <div>
                        <button
                            type="submit"
                            class="btn-action-save"
                            :disabled="form.processing"
                            title="Opslaan"
                            aria-label="Opslaan"
                        >
                            <DocumentCheckIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
                <p v-if="form.errors.category" class="text-sm text-red-400">{{ form.errors.category }}</p>
                <p v-if="form.errors.content" class="text-sm text-red-400">{{ form.errors.content }}</p>
                <p v-if="form.errors.link" class="text-sm text-red-400">{{ form.errors.link }}</p>
                <p v-if="form.errors.target_section" class="text-sm text-red-400">{{ form.errors.target_section }}</p>
            </form>

            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-4">
                <div v-if="!props.notes?.length" class="py-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
                    Nog geen notities. Voeg er een toe via de knop rechtsboven.
                </div>
                <div v-else class="space-y-2 md:space-y-0">
                    <div class="md:hidden space-y-2">
                        <div
                            v-for="note in props.notes"
                            :key="`note-mob-${note.id}`"
                            class="surface-brand-top rounded-xl border border-brand-blue/30 bg-app-panel px-4 py-3 text-app-ink shadow-sm dark:bg-app-panel-dark/95 dark:text-app-ink-dark"
                        >
                            <p class="text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Categorie</p>
                            <p>{{ note.category || '—' }}</p>
                            <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Inhoud</p>
                            <p class="whitespace-pre-wrap">{{ note.content || '—' }}</p>
                            <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Linkje</p>
                            <div class="flex items-start gap-1.5">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate">{{ note.link || '—' }}</p>
                                </div>
                                <a
                                    v-if="safeExternalUrl(note.link)"
                                    :href="safeExternalUrl(note.link)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mt-0.5 shrink-0 rounded p-0.5 text-brand-blue-light hover:bg-brand-blue/15 dark:text-brand-blue-light"
                                    :title="`Openen: ${linkDisplayText(note.link)}`"
                                    @click.stop
                                >
                                    <ArrowTopRightOnSquareIcon class="h-4 w-4" aria-hidden="true" />
                                    <span class="sr-only">Link openen</span>
                                </a>
                            </div>
                            <div class="mt-3 flex items-center gap-2 border-t border-brand-blue/25 pt-3 dark:border-brand-blue/35">
                                <button v-if="note.can_update" type="button" class="btn-action-edit" title="Bewerken" @click="editNote(note)">
                                    <PencilSquareIcon class="h-4 w-4" />
                                </button>
                                <button v-if="note.can_delete" type="button" class="btn-action-delete" title="Verwijderen" @click="deleteNote(note)">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="hidden overflow-x-auto rounded-lg border border-brand-blue/25 md:block">
                    <table class="w-full min-w-[42rem] border-collapse table-auto text-sm text-app-ink dark:text-app-ink-dark">
                    <colgroup>
                        <col class="w-[18%]" />
                        <col class="w-[38%]" />
                        <col class="w-[24%]" />
                        <col class="w-[20%]" />
                    </colgroup>
                    <thead class="border-b border-brand-blue/35 bg-app-sidebar dark:bg-app-canvas-dark/80">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                            <th class="px-3 py-2.5">Categorie</th>
                            <th class="px-3 py-2.5">Inhoud</th>
                            <th class="px-3 py-2.5">Linkje</th>
                            <th class="px-3 py-2.5 text-right sm:text-left">Acties</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-blue/25">
                        <tr
                            v-for="note in props.notes"
                            :key="note.id"
                            class="bg-brand-blue/5 transition-colors hover:bg-brand-blue/12 dark:bg-app-panel-dark/50 dark:hover:bg-brand-blue/15"
                        >
                            <td class="px-3 py-2.5 align-top">
                                <div class="text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">{{ note.category || '—' }}</div>
                            </td>
                            <td class="px-3 py-2.5 align-top">
                                <span class="whitespace-pre-wrap">{{ note.content || '—' }}</span>
                            </td>
                            <td class="px-3 py-2.5 align-top">
                                <div class="flex items-start gap-1.5">
                                    <div class="min-w-0 flex-1">
                                        <span class="truncate">{{ note.link || '—' }}</span>
                                    </div>
                                    <a
                                        v-if="safeExternalUrl(note.link)"
                                        :href="safeExternalUrl(note.link)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-0.5 shrink-0 rounded p-0.5 text-brand-blue-light hover:bg-brand-blue/15 dark:text-brand-blue-light"
                                        :title="`Openen: ${linkDisplayText(note.link)}`"
                                        @click.stop
                                    >
                                        <ArrowTopRightOnSquareIcon class="h-4 w-4" aria-hidden="true" />
                                        <span class="sr-only">Link openen</span>
                                    </a>
                                </div>
                            </td>
                            <td class="px-3 py-2.5 align-top">
                                <button v-if="note.can_update" type="button" class="btn-action-edit me-2" title="Bewerken" @click="editNote(note)">
                                    <PencilSquareIcon class="h-4 w-4" />
                                </button>
                                <button v-if="note.can_delete" type="button" class="btn-action-delete" title="Verwijderen" @click="deleteNote(note)">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
