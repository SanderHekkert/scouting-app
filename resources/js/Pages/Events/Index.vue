<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ events: Array });

const showAddForm = ref(false);
const showEditForm = ref(false);
const editingEventId = ref(null);

const form = useForm({
    theme: '',
    event_date: '',
    event_type: '',
    activity: '',
    program_by: '',
    absent: '',
    notes: '',
});

const editForm = useForm({
    theme: '',
    event_date: '',
    event_type: '',
    activity: '',
    program_by: '',
    absent: '',
    notes: '',
});

function toggleAddForm() {
    showAddForm.value = !showAddForm.value;
    if (showAddForm.value) {
        showEditForm.value = false;
        form.reset();
        form.clearErrors();
    }
}

function openEditForm(event) {
    if (!event) return;
    editingEventId.value = event.id;
    editForm.theme = event.theme ?? '';
    editForm.event_date = event.event_date ? String(event.event_date).slice(0, 10) : '';
    editForm.event_type = event.event_type ?? '';
    editForm.activity = event.activity ?? '';
    editForm.program_by = event.program_by ?? '';
    editForm.absent = event.absent ?? '';
    editForm.notes = event.notes ?? '';
    editForm.clearErrors();
    showEditForm.value = true;
    showAddForm.value = false;
}

function closeEditForm() {
    showEditForm.value = false;
    editingEventId.value = null;
    editForm.reset();
}

function submitAdd() {
    form.post(route('events.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAddForm.value = false;
        },
    });
}

function submitEdit() {
    if (!editingEventId.value) return;
    editForm.put(route('events.update', editingEventId.value), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditForm();
        },
    });
}

function deleteEvent(event) {
    if (!event?.id) return;
    if (!confirm('Dit agenda-item verwijderen?')) return;
    if (editingEventId.value === event.id) {
        closeEditForm();
    }
    router.delete(route('events.destroy', event.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Agenda" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Agenda</h2>
                <div class="flex flex-wrap items-center justify-end gap-2 sm:ms-auto">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-700"
                        @click="toggleAddForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Agenda-item toevoegen
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
                <h3 class="text-base font-semibold text-white">Nieuw agenda-item</h3>
                <div class="grid gap-4 sm:grid-cols-[8rem_1fr] sm:items-start">
                    <label for="add-event-theme" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Thema
                    </label>
                    <input
                        id="add-event-theme"
                        v-model="form.theme"
                        type="text"
                        placeholder="bv. Europa"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-event-date" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Datum
                    </label>
                    <input
                        id="add-event-date"
                        v-model="form.event_date"
                        type="date"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="add-event-type" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Type opkomst
                    </label>
                    <input
                        id="add-event-type"
                        v-model="form.event_type"
                        type="text"
                        placeholder="Normale opkomst"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-event-activity" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Activiteit
                    </label>
                    <input
                        id="add-event-activity"
                        v-model="form.activity"
                        type="text"
                        placeholder="Wat ga je doen?"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-event-program-by" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Programma door
                    </label>
                    <input
                        id="add-event-program-by"
                        v-model="form.program_by"
                        type="text"
                        placeholder="Naam"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-event-absent" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Afwezig
                    </label>
                    <input
                        id="add-event-absent"
                        v-model="form.absent"
                        type="text"
                        placeholder="Namen"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-event-notes" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Bijzonderheden
                    </label>
                    <textarea
                        id="add-event-notes"
                        v-model="form.notes"
                        rows="3"
                        placeholder="Optioneel"
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
                <p v-for="err in Object.values(form.errors)" :key="String(err)" class="text-sm text-red-400">
                    {{ err }}
                </p>
            </form>

            <form
                v-show="showEditForm"
                class="space-y-4 rounded-xl border border-amber-900/40 bg-gray-800/90 p-5 shadow-sm"
                @submit.prevent="submitEdit"
            >
                <h3 class="text-base font-semibold text-amber-100">Agenda-item bewerken</h3>
                <div class="grid gap-4 sm:grid-cols-[8rem_1fr] sm:items-start">
                    <label for="edit-event-theme" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Thema
                    </label>
                    <input
                        id="edit-event-theme"
                        v-model="editForm.theme"
                        type="text"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-event-date" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Datum
                    </label>
                    <input
                        id="edit-event-date"
                        v-model="editForm.event_date"
                        type="date"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-event-type" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Type opkomst
                    </label>
                    <input
                        id="edit-event-type"
                        v-model="editForm.event_type"
                        type="text"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-event-activity" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Activiteit
                    </label>
                    <input
                        id="edit-event-activity"
                        v-model="editForm.activity"
                        type="text"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-event-program-by" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Programma door
                    </label>
                    <input
                        id="edit-event-program-by"
                        v-model="editForm.program_by"
                        type="text"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-event-absent" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Afwezig
                    </label>
                    <input
                        id="edit-event-absent"
                        v-model="editForm.absent"
                        type="text"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-event-notes" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Bijzonderheden
                    </label>
                    <textarea
                        id="edit-event-notes"
                        v-model="editForm.notes"
                        rows="3"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
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
                <p v-for="err in Object.values(editForm.errors)" :key="`e-${String(err)}`" class="text-sm text-red-400">
                    {{ err }}
                </p>
            </form>

            <div class="rounded-xl bg-gray-800 p-4 shadow-sm">
                <div v-if="!props.events?.length" class="py-6 text-center text-sm text-gray-500">
                    Nog geen agenda-items.
                </div>
                <table v-else class="w-full table-fixed text-sm text-white">
                    <colgroup>
                        <col class="w-[20%]" />
                        <col class="w-[14%]" />
                        <col class="w-[16%]" />
                        <col class="w-[30%]" />
                        <col class="w-[20%]" />
                    </colgroup>
                    <thead>
                        <tr class="text-left text-gray-300">
                            <th class="pb-2">Thema</th>
                            <th class="pb-2">Datum</th>
                            <th class="pb-2">Type</th>
                            <th class="pb-2">Programma door</th>
                            <th class="pb-2 text-right sm:text-left">Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="event in props.events"
                            :key="event.id"
                            class="border-t border-gray-600"
                            :class="{ 'bg-gray-900/50': editingEventId === event.id }"
                        >
                            <td class="py-2 pr-2 align-top">{{ event.theme }}</td>
                            <td class="pr-2 align-top whitespace-nowrap">{{ event.event_date }}</td>
                            <td class="pr-2 align-top">{{ event.event_type }}</td>
                            <td class="align-top">{{ event.program_by }}</td>
                            <td class="py-2 align-top">
                                <div class="flex flex-wrap items-center justify-end gap-2 sm:justify-start">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded border border-gray-500 bg-gray-900 px-2 py-1 text-xs font-medium text-white hover:bg-gray-700"
                                        @click="openEditForm(event)"
                                    >
                                        <PencilSquareIcon class="h-4 w-4" />
                                        Bewerken
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded border border-red-800/60 bg-red-950/35 px-2 py-1 text-xs font-medium text-red-300 hover:bg-red-950/55"
                                        @click="deleteEvent(event)"
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
