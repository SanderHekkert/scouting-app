<script setup>
import AgendaSubnav from '@/Components/AgendaSubnav.vue';
import AgendaEventsTable from '@/Components/AgendaEventsTable.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    archivedEvents: Array,
    leaders: {
        type: Array,
        default: () => [],
    },
});

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
    <Head title="Gearchiveerde opkomsten" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Agenda</h2>
        </template>
        <div class="space-y-4 text-app-ink dark:text-app-ink-dark">
            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-4">
                <AgendaSubnav />

                <div class="mb-3 flex w-full flex-col gap-3 border-b border-brand-blue/35 pb-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-app-ink dark:text-app-ink-dark">
                            Gearchiveerde opkomsten
                            <span
                                class="ms-2 inline-flex align-middle rounded-full bg-slate-200/90 px-2 py-0.5 text-xs font-medium tabular-nums text-app-muted dark:bg-brand-blue/25 dark:text-app-muted-dark"
                            >
                                {{ props.archivedEvents?.length ?? 0 }}
                            </span>
                        </h3>
                        <p class="mt-0.5 text-xs text-app-muted dark:text-app-muted-dark">
                            Opkomsten van vóór vandaag (kalenderdag). Je kunt ze nog bewerken of verwijderen. Zet je de
                            datum op vandaag of later, dan komen ze weer bij actuele opkomsten.
                        </p>
                    </div>
                </div>

                <AgendaEventsTable
                    :events="props.archivedEvents"
                    :leaders="props.leaders"
                    :is-field-saving="isEventFieldSaving"
                    empty-message="Nog geen gearchiveerde opkomsten."
                    @patch-field="(ev, field, val) => patchEventField(ev, field, val)"
                    @delete="deleteEvent"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
