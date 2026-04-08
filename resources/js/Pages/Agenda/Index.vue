<script setup>
import AgendaItemsTable from '@/Components/AgendaItemsTable.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { DocumentCheckIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
});

const showAddForm = ref(false);
const form = useForm({
    theme: '',
    event_date: '',
    location: '',
    time_slot: '',
    invitees: '',
    link_url: '',
    attachment_file: null,
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
    form.post(route('agenda.store'), {
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

function editItem(item) {
    router.get(route('agenda.show', item.id));
}

function deleteItem(item) {
    if (!item?.id) return;
    if (!confirm('Dit agenda-item verwijderen?')) return;
    router.delete(route('agenda.destroy', item.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Agenda" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Agenda</h2>
                <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-app-panel px-3 py-2 text-sm font-medium text-app-ink shadow-sm transition hover:border-brand-blue/40 hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:border-brand-blue/45 dark:hover:bg-brand-blue/15" @click="toggleAddForm">
                    <PlusIcon class="h-5 w-5" />
                    Nieuwe activiteit toevoegen
                </button>
            </div>
        </template>

        <div class="space-y-4">
            <form v-show="showAddForm" class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark" @submit.prevent="submitAdd">
                <div class="grid gap-4 sm:grid-cols-[10rem_1fr] sm:items-start">
                    <label class="text-sm font-semibold sm:pt-2.5">Naam activiteit</label>
                    <input v-model="form.theme" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />
                    <label class="text-sm font-semibold sm:pt-2.5">Datum</label>
                    <input v-model="form.event_date" type="date" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />
                    <label class="text-sm font-semibold sm:pt-2.5">Locatie</label>
                    <input v-model="form.location" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />
                    <label class="text-sm font-semibold sm:pt-2.5">Tijdstip</label>
                    <input v-model="form.time_slot" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />
                    <label class="text-sm font-semibold sm:pt-2.5">Genodigden</label>
                    <textarea v-model="form.invitees" rows="2" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />
                    <label class="text-sm font-semibold sm:pt-2.5">URL</label>
                    <input v-model="form.link_url" type="url" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />
                    <label class="text-sm font-semibold sm:pt-2.5">Bijlage</label>
                    <input type="file" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" @change="onAttachmentChange" />
                    <label class="text-sm font-semibold sm:pt-2.5">Notities</label>
                    <textarea v-model="form.notes" rows="3" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />
                    <span class="hidden sm:block" />
                    <button type="submit" class="inline-flex items-center gap-2 rounded bg-brand-blue px-5 py-2 text-sm font-medium text-white hover:bg-brand-blue-dark disabled:opacity-50" :disabled="form.processing">
                        <DocumentCheckIcon class="h-5 w-5" />
                        Opslaan
                    </button>
                </div>
            </form>

            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                <h3 class="mb-3 text-lg font-semibold">Actuele agenda-items</h3>
                <AgendaItemsTable :items="props.items" empty-message="Nog geen actuele agenda-items." @edit="editItem" @delete="deleteItem" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
