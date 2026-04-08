<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftCircleIcon, DocumentCheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    item: { type: Object, required: true },
});

const form = useForm({
    theme: props.item.theme || '',
    event_date: String(props.item.event_date || '').slice(0, 10),
    location: props.item.location || '',
    time_slot: props.item.time_slot || '',
    invitees: props.item.invitees || '',
    link_url: props.item.link_url || '',
    attachment_file: null,
    notes: props.item.notes || '',
});

function submit() {
    form.transform((data) => ({ ...data, _method: 'patch' })).post(route('agenda.update', props.item.id), {
        forceFormData: true,
        preserveScroll: true,
    });
}

function onAttachmentChange(event) {
    form.attachment_file = event?.target?.files?.[0] || null;
}
</script>

<template>
    <Head title="Agenda-item bewerken" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Agenda-item bewerken</h2>
                <Link :href="route('agenda.index')" class="inline-flex items-center justify-center rounded border border-app-border p-2 text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15" title="Terug">
                    <ArrowLeftCircleIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>
        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 text-app-ink shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark dark:text-app-ink-dark" @submit.prevent="submit">
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
                <div class="space-y-2">
                    <a v-if="props.item.attachment_name" :href="route('agenda.attachment.download', props.item.id)" class="inline-flex text-sm text-brand-blue underline">{{ props.item.attachment_name }}</a>
                    <input type="file" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" @change="onAttachmentChange" />
                </div>
                <label class="text-sm font-semibold sm:pt-2.5">Notities</label>
                <textarea v-model="form.notes" rows="4" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />
                <span class="hidden sm:block" />
                <button type="submit" class="inline-flex items-center gap-2 rounded bg-brand-blue px-4 py-2 text-sm font-medium text-white hover:bg-brand-blue-dark disabled:opacity-50" :disabled="form.processing">
                    <DocumentCheckIcon class="h-5 w-5" />
                    Opslaan
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
