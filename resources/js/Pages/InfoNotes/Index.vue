<script setup>
import { ref } from 'vue';
import EditableTextCell from '@/Components/EditableTextCell.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ArrowTopRightOnSquareIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ notes: Array });

const showAddForm = ref(false);

const form = useForm({
    category: '',
    content: '',
    link: '',
});

const noteFieldSaving = ref(null);

function toggleAddForm() {
    showAddForm.value = !showAddForm.value;
    if (showAddForm.value) {
        form.reset();
        form.clearErrors();
    }
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

function deleteNote(note) {
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

function isNoteFieldSaving(note, field) {
    return noteFieldSaving.value === `${note.id}:${field}`;
}

function patchNoteField(note, field, raw) {
    if (!note?.id) return;
    let payload = {};
    if (field === 'category') {
        payload = { category: raw ?? '' };
    } else if (field === 'content') {
        payload = { content: raw ?? '' };
    } else if (field === 'link') {
        payload = { link: raw ?? '' };
    } else {
        return;
    }
    noteFieldSaving.value = `${note.id}:${field}`;
    router.patch(route('info-notes.quick-update', note.id), payload, {
        preserveScroll: true,
        onFinish: () => {
            noteFieldSaving.value = null;
        },
    });
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
                            class="rounded bg-brand-blue px-5 py-2 text-sm font-medium text-white hover:bg-brand-blue-dark disabled:opacity-50"
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
                            <EditableTextCell
                                :text="note.category ?? ''"
                                :multiline="false"
                                :saving="isNoteFieldSaving(note, 'category')"
                                @save="(v) => patchNoteField(note, 'category', v)"
                            />
                            <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Inhoud</p>
                            <EditableTextCell
                                :text="note.content ?? ''"
                                multiline
                                :saving="isNoteFieldSaving(note, 'content')"
                                @save="(v) => patchNoteField(note, 'content', v)"
                            />
                            <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Linkje</p>
                            <div class="flex items-start gap-1.5">
                                <div class="min-w-0 flex-1">
                                    <EditableTextCell
                                        :text="note.link ?? ''"
                                        :multiline="false"
                                        :saving="isNoteFieldSaving(note, 'link')"
                                        @save="(v) => patchNoteField(note, 'link', v)"
                                    />
                                </div>
                                <a
                                    v-if="note.link"
                                    :href="note.link"
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
                            <div class="mt-3 border-t border-brand-blue/25 pt-3 dark:border-brand-blue/35">
                                <button type="button" class="btn-action-delete" title="Verwijderen" @click="deleteNote(note)">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <table class="hidden w-full table-fixed text-sm text-app-ink dark:text-app-ink-dark md:table">
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
                        >
                            <td class="py-2 pr-3 align-top">
                                <div class="text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                                    <EditableTextCell
                                        :text="note.category ?? ''"
                                        :multiline="false"
                                        :saving="isNoteFieldSaving(note, 'category')"
                                        @save="(v) => patchNoteField(note, 'category', v)"
                                    />
                                </div>
                            </td>
                            <td class="align-top">
                                <EditableTextCell
                                    :text="note.content ?? ''"
                                    multiline
                                    :saving="isNoteFieldSaving(note, 'content')"
                                    @save="(v) => patchNoteField(note, 'content', v)"
                                />
                            </td>
                            <td class="py-2 align-top">
                                <div class="flex items-start gap-1.5">
                                    <div class="min-w-0 flex-1">
                                        <EditableTextCell
                                            :text="note.link ?? ''"
                                            :multiline="false"
                                            :saving="isNoteFieldSaving(note, 'link')"
                                            @save="(v) => patchNoteField(note, 'link', v)"
                                        />
                                    </div>
                                    <a
                                        v-if="note.link"
                                        :href="note.link"
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
                            <td class="py-2 align-top">
                                <button type="button" class="btn-action-delete" title="Verwijderen" @click="deleteNote(note)">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
