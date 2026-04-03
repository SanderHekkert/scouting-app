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
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Belangrijke info</h2>
                <div class="flex flex-wrap items-center justify-end gap-2 sm:ms-auto">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-700"
                        @click="toggleAddForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Info toevoegen
                    </button>
                </div>
            </div>
        </template>
        <div class="space-y-4 text-white">
            <form
                v-show="showAddForm"
                class="space-y-4 rounded-xl bg-gray-800 p-5 shadow-sm"
                @submit.prevent="submitAdd"
            >
                <h3 class="text-base font-semibold text-white">Nieuwe info</h3>
                <div class="grid gap-4 sm:grid-cols-[8rem_1fr] sm:items-start">
                    <label for="add-info-category" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Categorie
                    </label>
                    <input
                        id="add-info-category"
                        v-model="form.category"
                        type="text"
                        placeholder="Bijv. Kamp, Ouder contact"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-info-content" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Inhoud
                    </label>
                    <textarea
                        id="add-info-content"
                        v-model="form.content"
                        rows="4"
                        placeholder="Inhoud…"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-info-link" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Linkje
                    </label>
                    <input
                        id="add-info-link"
                        v-model="form.link"
                        type="text"
                        inputmode="url"
                        autocomplete="off"
                        placeholder="https://… of example.com"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <span class="hidden sm:block" aria-hidden="true" />
                    <div>
                        <button
                            type="submit"
                            class="rounded bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
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
                class="space-y-4 rounded-xl border border-amber-900/40 bg-gray-800/90 p-5 shadow-sm"
                @submit.prevent="submitEdit"
            >
                <h3 class="text-base font-semibold text-amber-100">Info bewerken</h3>
                <div class="grid gap-4 sm:grid-cols-[8rem_1fr] sm:items-start">
                    <label for="edit-info-category" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Categorie
                    </label>
                    <input
                        id="edit-info-category"
                        v-model="editForm.category"
                        type="text"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-info-content" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Inhoud
                    </label>
                    <textarea
                        id="edit-info-content"
                        v-model="editForm.content"
                        rows="4"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-info-link" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Linkje
                    </label>
                    <input
                        id="edit-info-link"
                        v-model="editForm.link"
                        type="text"
                        inputmode="url"
                        autocomplete="off"
                        placeholder="https://…"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <span class="hidden sm:block" aria-hidden="true" />
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="submit"
                            class="rounded bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                            :disabled="editForm.processing"
                        >
                            Bijwerken
                        </button>
                        <button
                            type="button"
                            class="rounded border border-gray-500 px-5 py-2 text-sm font-medium text-white hover:bg-gray-700"
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

            <div class="rounded-xl bg-gray-800 p-4 shadow-sm">
                <div v-if="!props.notes?.length" class="py-6 text-center text-sm text-gray-500">
                    Nog geen notities. Voeg er een toe via de knop rechtsboven.
                </div>
                <table v-else class="w-full table-fixed text-sm text-white">
                    <colgroup>
                        <col class="w-[18%]" />
                        <col class="w-[38%]" />
                        <col class="w-[24%]" />
                        <col class="w-[20%]" />
                    </colgroup>
                    <thead>
                        <tr class="text-left text-gray-300">
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
                            class="border-t border-gray-600"
                            :class="{ 'bg-gray-900/50': editingNoteId === note.id }"
                        >
                            <td class="py-2 pr-3 align-top text-xs uppercase tracking-wide text-gray-400">
                                {{ note.category }}
                            </td>
                            <td class="align-top whitespace-pre-wrap">{{ note.content }}</td>
                            <td class="py-2 align-top">
                                <a
                                    v-if="note.link"
                                    :href="note.link"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="break-all text-indigo-300 underline decoration-indigo-400/80 underline-offset-2 hover:text-indigo-200"
                                >
                                    {{ linkDisplayText(note.link) }}
                                </a>
                                <span v-else class="text-gray-500">—</span>
                            </td>
                            <td class="py-2 align-top">
                                <div class="flex flex-wrap items-center justify-end gap-2 sm:justify-start">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded border border-gray-500 bg-gray-900 px-2 py-1 text-xs font-medium text-white hover:bg-gray-700"
                                        @click="openEditForm(note)"
                                    >
                                        <PencilSquareIcon class="h-4 w-4" />
                                        Bewerken
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded border border-red-800/60 bg-red-950/35 px-2 py-1 text-xs font-medium text-red-300 hover:bg-red-950/55"
                                        @click="deleteNote(note)"
                                    >
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
