<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ notes: Array });

const showAddForm = ref(false);
const showEditForm = ref(false);
const editingNoteId = ref(null);

const form = useForm({
    category: '',
    content: '',
    link: '',
});

const editForm = useForm({
    category: '',
    content: '',
    link: '',
});

function toggleAddForm() {
    showAddForm.value = !showAddForm.value;
    if (showAddForm.value) {
        showEditForm.value = false;
        form.reset();
        form.clearErrors();
    }
}

function openEditForm(note) {
    if (!note) return;
    editingNoteId.value = note.id;
    editForm.category = note.category ?? '';
    editForm.content = note.content ?? '';
    editForm.link = note.link ?? '';
    editForm.clearErrors();
    showEditForm.value = true;
    showAddForm.value = false;
}

function closeEditForm() {
    showEditForm.value = false;
    editingNoteId.value = null;
    editForm.reset();
}

function submitAdd() {
    form.post(route('info-notes.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAddForm.value = false;
        },
    });
}

function submitEdit() {
    if (!editingNoteId.value) return;
    editForm.put(route('info-notes.update', editingNoteId.value), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditForm();
        },
    });
}

function deleteNote(note) {
    if (!note?.id) return;
    if (!confirm('Deze notitie verwijderen?')) return;
    if (editingNoteId.value === note.id) {
        closeEditForm();
    }
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
</script>

<template>
    <Head title="Belangrijke info" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Belangrijke info</h2>
                <div class="flex flex-wrap items-center justify-end gap-2 sm:ms-auto">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-app-panel px-3 py-2 text-sm font-medium text-app-ink shadow-sm transition hover:border-brand-blue/40 hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:border-brand-blue/45 dark:hover:bg-brand-blue/15"
                        @click="toggleAddForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Info toevoegen
                    </button>
                </div>
            </div>
        </template>
        <div class="space-y-4 text-app-ink dark:text-app-ink-dark">
            <form
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
                            class="rounded bg-brand-red px-5 py-2 text-sm font-medium text-white hover:bg-brand-red-dark disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            Opslaan
                        </button>
                    </div>
                </div>
                <p v-if="form.errors.category" class="text-sm text-red-400">{{ form.errors.category }}</p>
                <p v-if="form.errors.content" class="text-sm text-red-400">{{ form.errors.content }}</p>
                <p v-if="form.errors.link" class="text-sm text-red-400">{{ form.errors.link }}</p>
            </form>

            <form
                v-show="showEditForm"
                class="surface-brand-top space-y-4 rounded-xl border border-brand-yellow/35 bg-app-panel shadow-sm dark:bg-app-panel-dark/95 p-5"
                @submit.prevent="submitEdit"
            >
                <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Info bewerken</h3>
                <div class="grid gap-4 sm:grid-cols-[8rem_1fr] sm:items-start">
                    <label for="edit-info-category" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Categorie
                    </label>
                    <input
                        id="edit-info-category"
                        v-model="editForm.category"
                        type="text"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    />

                    <label for="edit-info-content" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Inhoud
                    </label>
                    <textarea
                        id="edit-info-content"
                        v-model="editForm.content"
                        rows="4"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    />

                    <label for="edit-info-link" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Linkje
                    </label>
                    <input
                        id="edit-info-link"
                        v-model="editForm.link"
                        type="text"
                        inputmode="url"
                        autocomplete="off"
                        placeholder="https://…"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <span class="hidden sm:block" aria-hidden="true" />
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="submit"
                            class="rounded bg-brand-red px-5 py-2 text-sm font-medium text-white hover:bg-brand-red-dark disabled:opacity-50"
                            :disabled="editForm.processing"
                        >
                            Bijwerken
                        </button>
                        <button
                            type="button"
                            class="rounded border border-brand-blue-light/50 px-5 py-2 text-sm font-medium text-app-ink dark:text-app-ink-dark transition hover:bg-brand-blue/20"
                            @click="closeEditForm"
                        >
                            Annuleren
                        </button>
                    </div>
                </div>
                <p v-if="editForm.errors.category" class="text-sm text-red-400">{{ editForm.errors.category }}</p>
                <p v-if="editForm.errors.content" class="text-sm text-red-400">{{ editForm.errors.content }}</p>
                <p v-if="editForm.errors.link" class="text-sm text-red-400">{{ editForm.errors.link }}</p>
            </form>

            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-4">
                <div v-if="!props.notes?.length" class="py-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
                    Nog geen notities. Voeg er een toe via de knop rechtsboven.
                </div>
                <table v-else class="w-full table-fixed text-sm text-app-ink dark:text-app-ink-dark">
                    <colgroup>
                        <col class="w-[18%]" />
                        <col class="w-[38%]" />
                        <col class="w-[24%]" />
                        <col class="w-[20%]" />
                    </colgroup>
                    <thead>
                        <tr class="text-left text-app-muted dark:text-app-muted-dark">
                            <th class="pb-2">Categorie</th>
                            <th class="pb-2">Inhoud</th>
                            <th class="pb-2">Linkje</th>
                            <th class="pb-2 text-right sm:text-left">Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="note in props.notes"
                            :key="note.id"
                            class="border-t border-brand-blue/35"
                            :class="{ 'bg-brand-blue/5 dark:bg-app-canvas-dark/80': editingNoteId === note.id }"
                        >
                            <td class="py-2 pr-3 align-top text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                                {{ note.category }}
                            </td>
                            <td class="align-top whitespace-pre-wrap">{{ note.content }}</td>
                            <td class="py-2 align-top">
                                <a
                                    v-if="note.link"
                                    :href="note.link"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="break-all text-brand-blue-light underline decoration-brand-blue-light/70 underline-offset-2 hover:text-brand-blue-dark dark:text-brand-blue-light dark:hover:text-app-ink-dark"
                                >
                                    {{ linkDisplayText(note.link) }}
                                </a>
                                <span v-else class="text-app-muted dark:text-app-muted-dark">—</span>
                            </td>
                            <td class="py-2 align-top">
                                <div class="flex flex-wrap items-center justify-end gap-2 sm:justify-start">
                                    <button type="button" class="btn-action-edit" @click="openEditForm(note)">
                                        <PencilSquareIcon class="h-4 w-4" />
                                        Bewerken
                                    </button>
                                    <button type="button" class="btn-action-delete" @click="deleteNote(note)">
                                        <TrashIcon class="h-4 w-4" />
                                        Verwijderen
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
