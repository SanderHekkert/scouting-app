<script setup>
import AgendaSubnav from '@/Components/AgendaSubnav.vue';
import AgendaEventsTable from '@/Components/AgendaEventsTable.vue';
import { computed, nextTick, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { DocumentCheckIcon, PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    events: Array,
    leaders: {
        type: Array,
        default: () => [],
    },
    taskItems: {
        type: Array,
        default: () => [],
    },
    allSections: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const activeSection = computed(() => page.props.auth?.active_section ?? 'dolfijnen');
const eventPerms = computed(() => page.props.auth?.permissions?.events ?? {});
const canCreateEvents = computed(() => !!eventPerms.value.create);
const canUpdateEvents = computed(() => !!eventPerms.value.update);
const canDeleteEvents = computed(() => !!eventPerms.value.delete);
const canManageEvents = computed(() => canCreateEvents.value || canUpdateEvents.value || canDeleteEvents.value);
const currentRole = computed(() => {
    const roles = page.props.auth?.section_roles ?? [];
    return roles.find((r) => r.section === activeSection.value)?.role ?? null;
});
const canManageAgenda = computed(() => currentRole.value !== 'lid' && canManageEvents.value);
const canMarkOwnPresence = computed(
    () => currentRole.value === 'lid' && ['dolfijnen', 'bevers', 'zeeverkenners', 'wilde_vaart', 'loodsen'].includes(activeSection.value),
);
const currentUserName = computed(() => {
    const first = String(page.props.auth?.user?.first_name ?? '').trim();
    const last = String(page.props.auth?.user?.last_name ?? '').trim();
    const full = `${first} ${last}`.trim();
    return full || String(page.props.auth?.user?.name ?? '').trim();
});
const isBestuur = computed(() => activeSection.value === 'bestuur');
const singularLabel = computed(() => (isBestuur.value ? 'agenda-item' : 'opkomst'));
const pluralLabel = computed(() => (isBestuur.value ? 'agenda-items' : 'opkomsten'));
const typeLabel = computed(() => (isBestuur.value ? 'Type' : 'Type opkomst'));
const addButtonLabel = computed(() => (isBestuur.value ? 'Nieuwe activiteit toevoegen' : `${singularLabel.value} toevoegen`));
const addFormTitle = computed(() => (isBestuur.value ? 'Nieuwe activiteit' : 'Nieuw agenda-item'));

/** Query ?event=123: focus op die rij na navigatie vanaf dashboard */
const highlightEventId = computed(() => {
    const raw = page.url.includes('?') ? page.url.split('?')[1] : '';
    const id = new URLSearchParams(raw).get('event');
    const n = id ? Number.parseInt(id, 10) : NaN;
    return Number.isFinite(n) ? n : null;
});

function scrollToHighlightedRow() {
    const id = highlightEventId.value;
    if (id == null) return;
    nextTick(() => {
        document.getElementById(`agenda-event-row-${id}`)?.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
    });
}

watch(highlightEventId, scrollToHighlightedRow, { immediate: true });

const showAddForm = ref(false);

const form = useForm({
    theme: '',
    event_date: '',
    event_type: '',
    activity: '',
    program_by: '',
    location: '',
    time_slot: '',
    invitees: '',
    link_url: '',
    attachments: '',
    attachment_file: null,
    absent: '',
    notes: '',
    shared_sections: [],
});
const sectionLabels = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const shareableSections = computed(() =>
    (props.allSections || []).filter((s) => s !== activeSection.value && !['bestuur', 'loodsen'].includes(s)),
);

function toggleAddForm() {
    if (!canCreateEvents.value) return;
    showAddForm.value = !showAddForm.value;
    if (showAddForm.value) {
        form.reset();
        form.clearErrors();
    }
}

function goToCreateEvent() {
    if (!canCreateEvents.value) return;
    router.get(route('opkomsten.create'));
}

function submitAdd() {
    if (!canCreateEvents.value) return;
    form.post(route('opkomsten.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAddForm.value = false;
        },
    });
}

function onAttachmentChange(event) {
    form.attachment_file = event?.target?.files?.[0] || null;
}

function deleteEvent(event) {
    if (!canDeleteEvents.value) return;
    if (!event?.id) return;
    if (!confirm('Dit agenda-item verwijderen?')) return;
    router.delete(route('opkomsten.destroy', event.id), {
        preserveScroll: true,
    });
}

function editEvent(event) {
    if (!canUpdateEvents.value) return;
    if (!event?.id) return;
    router.get(route('opkomsten.show', event.id));
}

function setOwnAttendance(event, present) {
    if (!event?.id) return;
    router.patch(
        route('opkomsten.attendance.update', event.id),
        { present },
        { preserveScroll: true },
    );
}

</script>

<template>
    <Head title="Opkomsten" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Opkomsten</h2>
                <div v-if="canCreateEvents" class="flex flex-wrap items-center justify-end gap-2 sm:ms-auto">
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-blue text-white shadow-sm transition hover:bg-brand-blue-dark"
                        title="Opkomst toevoegen"
                        aria-label="Opkomst toevoegen"
                        @click="goToCreateEvent"
                    >
                        <PlusIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </template>
        <div class="space-y-4 text-app-ink dark:text-app-ink-dark">
            <form
                v-if="canCreateEvents"
                v-show="showAddForm"
                class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-5"
                @submit.prevent="submitAdd"
            >
                <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">{{ addFormTitle }}</h3>
                <div class="grid gap-4 sm:grid-cols-[8rem_1fr] sm:items-start">
                    <label v-if="!isBestuur" for="add-event-theme" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Thema
                    </label>
                    <input
                        v-if="!isBestuur"
                        id="add-event-theme"
                        v-model="form.theme"
                        type="text"
                        placeholder="Thema"
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

                    <label v-if="!isBestuur" for="add-event-type" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Type opkomst
                    </label>
                    <input
                        v-if="!isBestuur"
                        id="add-event-type"
                        v-model="form.event_type"
                        type="text"
                        placeholder="Opkomst"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-event-activity" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        {{ isBestuur ? 'Naam activiteit' : 'Wat ga je doen?' }}
                    </label>
                    <input
                        v-if="isBestuur"
                        id="add-event-activity"
                        v-model="form.theme"
                        type="text"
                        placeholder="Naam activiteit"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />
                    <input
                        v-else
                        id="add-event-activity"
                        v-model="form.activity"
                        type="text"
                        placeholder="Bv. knutselen, kampvuur"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <template v-if="isBestuur">
                        <label for="add-event-location" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                            Locatie
                        </label>
                        <input
                            id="add-event-location"
                            v-model="form.location"
                            type="text"
                            placeholder="Locatie"
                            class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                        />

                        <label for="add-event-time-slot" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                            Tijdstip
                        </label>
                        <input
                            id="add-event-time-slot"
                            v-model="form.time_slot"
                            type="text"
                            placeholder="Bijv. 19:30 - 21:00"
                            class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                        />

                        <label for="add-event-invitees" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                            Genodigden
                        </label>
                        <textarea
                            id="add-event-invitees"
                            v-model="form.invitees"
                            rows="2"
                            placeholder="Wie zijn uitgenodigd?"
                            class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                        />

                        <label for="add-event-url" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                            URL
                        </label>
                        <input
                            id="add-event-url"
                            v-model="form.link_url"
                            type="url"
                            placeholder="https://..."
                            class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                        />

                        <label for="add-event-attachments" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                            Bijlagen
                        </label>
                        <input
                            id="add-event-attachments"
                            type="file"
                            @change="onAttachmentChange"
                            class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                        />
                    </template>

                    <label v-if="!isBestuur" for="add-event-program-by" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Programma door
                    </label>
                    <input
                        v-if="!isBestuur"
                        id="add-event-program-by"
                        v-model="form.program_by"
                        type="text"
                        placeholder="Naam"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label v-if="!isBestuur" for="add-event-absent" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Afwezig
                    </label>
                    <input
                        v-if="!isBestuur"
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

                    <label v-if="!isBestuur" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Gezamenlijk met
                    </label>
                    <div v-if="!isBestuur" class="flex flex-wrap gap-2">
                        <label
                            v-for="section in shareableSections"
                            :key="`add-share-${section}`"
                            class="inline-flex items-center gap-2 rounded border border-app-border bg-white px-2 py-1 text-xs dark:border-app-border-dark dark:bg-app-canvas-dark"
                        >
                            <input
                                v-model="form.shared_sections"
                                type="checkbox"
                                :value="section"
                                class="rounded border-app-border"
                            />
                            {{ sectionLabels[section] || section }}
                        </label>
                    </div>

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
                <p v-for="err in Object.values(form.errors)" :key="String(err)" class="text-sm text-red-400">
                    {{ err }}
                </p>
            </form>

            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-4">
                <AgendaSubnav />

                <div class="mb-3 flex w-full flex-col gap-3 border-b border-brand-blue/35 pb-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-app-ink dark:text-app-ink-dark">
                            Actuele {{ pluralLabel }}
                            <span
                                class="ms-2 inline-flex align-middle rounded-full bg-slate-200/90 px-2 py-0.5 text-xs font-medium tabular-nums text-app-muted dark:bg-brand-blue/25 dark:text-app-muted-dark"
                            >
                                {{ props.events?.length ?? 0 }}
                            </span>
                        </h3>
                    </div>
                </div>

                <AgendaEventsTable
                    :events="props.events"
                    :leaders="props.leaders"
                    :task-items="props.taskItems"
                    :is-bestuur="isBestuur"
                    :highlight-event-id="highlightEventId"
                    :is-field-saving="() => false"
                    :can-edit-agenda="canUpdateEvents || canDeleteEvents"
                    :can-mark-own-presence="canMarkOwnPresence"
                    :current-user-name="currentUserName"
                    :type-label="typeLabel"
                    :empty-message="`Nog geen actuele ${pluralLabel}.`"
                    @delete="deleteEvent"
                    @edit="editEvent"
                    @set-own-attendance="(ev, present) => setOwnAttendance(ev, present)"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
