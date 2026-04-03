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

const agendaMonthLabels = ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];

function formatAgendaDate(value) {
    if (value == null || value === '') return '–';
    const s = String(value).slice(0, 10);
    const parts = s.split('-');
    if (parts.length !== 3) return s;
    const [y, m, d] = parts.map((p) => parseInt(p, 10));
    if (!y || !m || !d) return s;
    const label = agendaMonthLabels[m - 1];
    if (!label) return s;
    return `${d}-${label}`;
}

function dashIfEmpty(value) {
    if (value == null || String(value).trim() === '') return '–';
    return value;
}
</script>

<template>
    <Head title="Agenda" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Agenda</h2>
                <div class="flex flex-wrap items-center justify-end gap-2 sm:ms-auto">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-app-panel px-3 py-2 text-sm font-medium text-app-ink shadow-sm transition hover:border-brand-blue/40 hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:border-brand-blue/45 dark:hover:bg-brand-blue/15"
                        @click="toggleAddForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Agenda-item toevoegen
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
                <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Nieuw agenda-item</h3>
                <div class="grid gap-4 sm:grid-cols-[8rem_1fr] sm:items-start">
                    <label for="add-event-theme" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Thema
                    </label>
                    <input
                        id="add-event-theme"
                        v-model="form.theme"
                        type="text"
                        placeholder="Optioneel, bv. Europa"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-event-date" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Datum
                    </label>
                    <input
                        id="add-event-date"
                        v-model="form.event_date"
                        type="date"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    />

                    <label for="add-event-type" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Type opkomst
                    </label>
                    <input
                        id="add-event-type"
                        v-model="form.event_type"
                        type="text"
                        placeholder="Normale opkomst"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-event-activity" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Wat ga je doen?
                    </label>
                    <input
                        id="add-event-activity"
                        v-model="form.activity"
                        type="text"
                        placeholder="Bv. knutselen, kampvuur"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-event-program-by" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Programma door
                    </label>
                    <input
                        id="add-event-program-by"
                        v-model="form.program_by"
                        type="text"
                        placeholder="Naam"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-event-absent" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Afwezig
                    </label>
                    <input
                        id="add-event-absent"
                        v-model="form.absent"
                        type="text"
                        placeholder="Namen"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-event-notes" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Bijzonderheden
                    </label>
                    <textarea
                        id="add-event-notes"
                        v-model="form.notes"
                        rows="3"
                        placeholder="Optioneel"
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
                <p v-for="err in Object.values(form.errors)" :key="String(err)" class="text-sm text-red-400">
                    {{ err }}
                </p>
            </form>

            <form
                v-show="showEditForm"
                class="surface-brand-top space-y-4 rounded-xl border border-brand-yellow/35 bg-app-panel shadow-sm dark:bg-app-panel-dark/95 p-5"
                @submit.prevent="submitEdit"
            >
                <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Agenda-item bewerken</h3>
                <div class="grid gap-4 sm:grid-cols-[8rem_1fr] sm:items-start">
                    <label for="edit-event-theme" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Thema
                    </label>
                    <input
                        id="edit-event-theme"
                        v-model="editForm.theme"
                        type="text"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    />

                    <label for="edit-event-date" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Datum
                    </label>
                    <input
                        id="edit-event-date"
                        v-model="editForm.event_date"
                        type="date"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    />

                    <label for="edit-event-type" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Type opkomst
                    </label>
                    <input
                        id="edit-event-type"
                        v-model="editForm.event_type"
                        type="text"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    />

                    <label for="edit-event-activity" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Wat ga je doen?
                    </label>
                    <input
                        id="edit-event-activity"
                        v-model="editForm.activity"
                        type="text"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    />

                    <label for="edit-event-program-by" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Programma door
                    </label>
                    <input
                        id="edit-event-program-by"
                        v-model="editForm.program_by"
                        type="text"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    />

                    <label for="edit-event-absent" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Afwezig
                    </label>
                    <input
                        id="edit-event-absent"
                        v-model="editForm.absent"
                        type="text"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    />

                    <label for="edit-event-notes" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Bijzonderheden
                    </label>
                    <textarea
                        id="edit-event-notes"
                        v-model="editForm.notes"
                        rows="3"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
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
                <p v-for="err in Object.values(editForm.errors)" :key="`e-${String(err)}`" class="text-sm text-red-400">
                    {{ err }}
                </p>
            </form>

            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-4">
                <div v-if="!props.events?.length" class="py-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
                    Nog geen agenda-items.
                </div>
                <div v-else class="surface-brand-top-lg overflow-x-auto rounded-lg border border-brand-blue/25">
                    <table class="w-full min-w-[72rem] border-collapse text-left text-sm text-app-ink dark:text-app-ink-dark">
                        <thead class="border-b border-brand-blue/35 bg-app-sidebar dark:bg-app-canvas-dark/80">
                            <tr class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                                <th scope="col" class="min-w-[7rem] px-3 py-2.5">Thema</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Datum</th>
                                <th scope="col" class="min-w-[8rem] px-3 py-2.5">Type opkomst</th>
                                <th scope="col" class="min-w-[10rem] px-3 py-2.5">Wat ga je doen?</th>
                                <th scope="col" class="min-w-[7rem] px-3 py-2.5">Programma door</th>
                                <th scope="col" class="min-w-[12rem] px-3 py-2.5">Afwezig</th>
                                <th scope="col" class="min-w-[11rem] px-3 py-2.5">Bijzonderheden</th>
                                <th scope="col" class="min-w-[9rem] whitespace-nowrap px-3 py-2.5 text-end sm:text-start">
                                    Acties
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-blue/25">
                            <tr
                                v-for="event in props.events"
                                :key="event.id"
                                class="bg-brand-blue/5 transition-colors hover:bg-brand-blue/12 dark:bg-app-panel-dark/50 dark:hover:bg-brand-blue/15"
                                :class="{ '!bg-brand-blue/15 dark:!bg-app-canvas-dark/90': editingEventId === event.id }"
                            >
                                <td class="px-3 py-2.5 align-top">
                                    <span class="line-clamp-3 break-words">{{ dashIfEmpty(event.theme) }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 align-top tabular-nums">
                                    {{ formatAgendaDate(event.event_date) }}
                                </td>
                                <td class="px-3 py-2.5 align-top">
                                    <span class="line-clamp-2 break-words">{{ dashIfEmpty(event.event_type) }}</span>
                                </td>
                                <td class="px-3 py-2.5 align-top">
                                    <span class="line-clamp-3 break-words whitespace-pre-wrap">{{ dashIfEmpty(event.activity) }}</span>
                                </td>
                                <td class="px-3 py-2.5 align-top">
                                    <span class="line-clamp-2 break-words">{{ dashIfEmpty(event.program_by) }}</span>
                                </td>
                                <td class="max-w-[18rem] px-3 py-2.5 align-top">
                                    <span class="line-clamp-4 break-words text-sm leading-snug">{{ dashIfEmpty(event.absent) }}</span>
                                </td>
                                <td class="max-w-[16rem] px-3 py-2.5 align-top">
                                    <span class="line-clamp-3 break-words">{{ dashIfEmpty(event.notes) }}</span>
                                </td>
                                <td class="px-3 py-2.5 align-top">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end lg:justify-start">
                                        <button type="button" class="btn-action-edit" @click="openEditForm(event)">
                                            <PencilSquareIcon class="h-4 w-4 shrink-0" />
                                            Bewerken
                                        </button>
                                        <button type="button" class="btn-action-delete" @click="deleteEvent(event)">
                                            <TrashIcon class="h-4 w-4 shrink-0" />
                                            Verwijderen
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
