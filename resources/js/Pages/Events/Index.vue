<script setup>
import AgendaSubnav from '@/Components/AgendaSubnav.vue';
import AgendaEventsTable from '@/Components/AgendaEventsTable.vue';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    events: Array,
});

const showAddForm = ref(false);

const form = useForm({
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
        form.reset();
        form.clearErrors();
    }
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

function deleteEvent(event) {
    if (!event?.id) return;
    if (!confirm('Dit agenda-item verwijderen?')) return;
    router.delete(route('events.destroy', event.id), {
        preserveScroll: true,
    });
}

const eventFieldSaving = ref(null);

function patchEventField(event, field, raw) {
    eventFieldSaving.value = `${event.id}:${field}`;
    router.patch(
        route('events.quick-update', event.id),
        { [field]: raw ?? '' },
        {
            preserveScroll: true,
            onFinish: () => {
                eventFieldSaving.value = null;
            },
        },
    );
}

function isEventFieldSaving(event, field) {
    return eventFieldSaving.value === `${event.id}:${field}`;
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

            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-4">
                <AgendaSubnav />

                <div class="mb-3 flex w-full flex-col gap-3 border-b border-brand-blue/35 pb-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-app-ink dark:text-app-ink-dark">Actuele opkomsten</h3>
                        <p class="mt-0.5 text-xs text-app-muted dark:text-app-muted-dark">
                            Vanaf vandaag en verder. Opkomsten van vóór vandaag (kalenderdag) staan onder het tabblad
                            Gearchiveerde opkomsten. Dubbelklik in een cel om te bewerken.
                        </p>
                    </div>
                </div>

                <AgendaEventsTable
                    :events="props.events"
                    :is-field-saving="isEventFieldSaving"
                    empty-message="Nog geen actuele opkomsten."
                    @patch-field="(ev, field, val) => patchEventField(ev, field, val)"
                    @delete="deleteEvent"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
