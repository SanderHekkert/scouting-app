<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon, DocumentCheckIcon, PaperClipIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { computed, ref, watch } from 'vue';
import { useSaveRedirect } from '@/utils/saveForm';

const props = defineProps({
    item: { type: Object, required: true },
    isBestuur: { type: Boolean, default: false },
    availableUsers: { type: Array, default: () => [] },
});
const { applySaveRedirect, saveFormOptions } = useSaveRedirect();
const page = usePage();
const sectionLabels = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const speltakLabel = computed(() => sectionLabels[page.props.auth?.active_section] || 'Dolfijnen');
const isEditing = computed(() => !!props.item?.id);
const pageTitle = computed(() =>
    isEditing.value
        ? `${speltakLabel.value} - Agenda activiteit bewerken`
        : `${speltakLabel.value} - Agenda activiteit toevoegen`,
);

const form = useForm({
    theme: props.item.theme || '',
    event_date: String(props.item.event_date || '').slice(0, 10),
    end_date: String(props.item.end_date || props.item.event_date || '').slice(0, 10),
    start_time: props.item.start_time || '',
    end_time: props.item.end_time || '',
    location: props.item.location || '',
    invitees: props.item.invitees || '',
    link_url: props.item.link_url || '',
    attachment_file: null,
    notes: props.item.notes || '',
    audience_scope: props.item.audience_scope || 'self',
    target_user_ids: Array.isArray(props.item.target_user_ids) ? [...props.item.target_user_ids] : [],
});

const attachmentInput = ref(null);

function onAttachmentChange(event) {
    form.attachment_file = event?.target?.files?.[0] || null;
}

function openAttachmentPicker() {
    attachmentInput.value?.click();
}

function clearAttachment() {
    form.attachment_file = null;
    if (attachmentInput.value) {
        attachmentInput.value.value = '';
    }
}

function submit() {
    const options = saveFormOptions({
        forceFormData: true,
    });
    const redirectPayload = (data) => applySaveRedirect(data);

    if (isEditing.value) {
        form
            .transform((data) => redirectPayload({ ...data, _method: 'put' }))
            .post(route('agenda.update', props.item.id), options);
        return;
    }

    form
        .transform((data) => redirectPayload(data))
        .post(route('agenda.store'), options);
}

function addTargetUser(event) {
    const id = Number(event?.target?.value || 0);
    if (!id) return;
    if (!form.target_user_ids.includes(id)) {
        form.target_user_ids.push(id);
    }
    event.target.value = '';
}

function removeTargetUser(id) {
    form.target_user_ids = form.target_user_ids.filter((v) => Number(v) !== Number(id));
}

function parseInviteeEmails(raw) {
    return String(raw || '')
        .split(/[\n,;]+/)
        .map((v) => v.trim().toLowerCase())
        .filter((v, index, arr) => v !== '' && arr.indexOf(v) === index);
}

function setInviteeEmails(emails) {
    form.invitees = emails.join(', ');
}

function addInviteeUser(event) {
    const email = String(event?.target?.value || '').trim().toLowerCase();
    if (!email) return;
    const emails = parseInviteeEmails(form.invitees);
    if (!emails.includes(email)) {
        emails.push(email);
        setInviteeEmails(emails);
    }
    event.target.value = '';
}

function removeInviteeUser(email) {
    const emails = parseInviteeEmails(form.invitees).filter((v) => v !== String(email || '').toLowerCase());
    setInviteeEmails(emails);
}

const selectedTargetUsers = computed(() => {
    const ids = new Set((form.target_user_ids || []).map((v) => Number(v)));
    return (props.availableUsers || []).filter((u) => ids.has(Number(u.id)));
});

const selectableTargetUsers = computed(() => {
    const ids = new Set((form.target_user_ids || []).map((v) => Number(v)));
    return (props.availableUsers || []).filter((u) => !ids.has(Number(u.id)));
});

const selectedInviteeUsers = computed(() => {
    const emails = new Set(parseInviteeEmails(form.invitees));
    return (props.availableUsers || []).filter((u) => emails.has(String(u.email || '').trim().toLowerCase()));
});

const selectableInviteeUsers = computed(() => {
    const emails = new Set(parseInviteeEmails(form.invitees));
    return (props.availableUsers || []).filter((u) => !emails.has(String(u.email || '').trim().toLowerCase()));
});

const attachmentFileName = computed(() => {
    if (form.attachment_file?.name) {
        return form.attachment_file.name;
    }
    if (props.item.attachment_name) {
        return props.item.attachment_name;
    }
    return 'Geen bestand gekozen';
});

watch(
    () => form.audience_scope,
    (value) => {
        if (value !== 'selected') {
            form.target_user_ids = [];
        }
    },
);
</script>

<template>
    <Head :title="pageTitle" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ pageTitle }}</h2>
                <Link :href="route('agenda.index')" class="btn-action-back" title="Terug" aria-label="Terug">
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 text-black shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark dark:text-app-ink-dark" @submit.prevent="submit">
            <div v-if="Object.keys(form.errors).length" class="rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700">
                <p class="font-semibold">Opslaan mislukt. Controleer de velden.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-[10rem_1fr] sm:items-start">
                <label class="text-sm font-semibold text-black sm:pt-2.5 dark:text-app-ink-dark">Naam activiteit</label>
                <div>
                    <input v-model="form.theme" type="text" class="min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />
                    <p v-if="form.errors.theme" class="mt-1 text-xs text-red-600">{{ form.errors.theme }}</p>
                </div>
                <label class="text-sm font-semibold text-black sm:pt-2.5 dark:text-app-ink-dark">Datum van</label>
                <div>
                    <input v-model="form.event_date" type="date" class="min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />
                    <p v-if="form.errors.event_date" class="mt-1 text-xs text-red-600">{{ form.errors.event_date }}</p>
                </div>
                <label class="text-sm font-semibold text-black sm:pt-2.5 dark:text-app-ink-dark">Datum tot</label>
                <div>
                    <input v-model="form.end_date" type="date" class="min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />
                    <p v-if="form.errors.end_date" class="mt-1 text-xs text-red-600">{{ form.errors.end_date }}</p>
                </div>
                <label class="text-sm font-semibold text-black sm:pt-2.5 dark:text-app-ink-dark">Locatie</label>
                <div>
                    <input v-model="form.location" type="text" class="min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />
                    <p v-if="form.errors.location" class="mt-1 text-xs text-red-600">{{ form.errors.location }}</p>
                </div>
                <label class="text-sm font-semibold text-black sm:pt-2.5 dark:text-app-ink-dark">Tijd van</label>
                <div>
                    <input v-model="form.start_time" type="time" class="min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />
                    <p v-if="form.errors.start_time" class="mt-1 text-xs text-red-600">{{ form.errors.start_time }}</p>
                </div>
                <label class="text-sm font-semibold text-black sm:pt-2.5 dark:text-app-ink-dark">Tijd tot</label>
                <div>
                    <input v-model="form.end_time" type="time" class="min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />
                    <p v-if="form.errors.end_time" class="mt-1 text-xs text-red-600">{{ form.errors.end_time }}</p>
                </div>
                <label class="text-sm font-semibold text-black sm:pt-2.5 dark:text-app-ink-dark">Genodigden</label>
                <div class="space-y-2">
                    <select class="min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:[&>option]:bg-app-canvas-dark dark:[&>option]:text-app-ink-dark" @change="addInviteeUser">
                        <option value="">Klik om genodigde toe te voegen...</option>
                        <option v-for="user in selectableInviteeUsers" :key="`invitee-option-${user.id}`" :value="user.email">
                            {{ user.name }}
                        </option>
                    </select>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="user in selectedInviteeUsers"
                            :key="`invitee-chip-${user.id}`"
                            class="inline-flex items-center gap-2 rounded-full bg-brand-blue/15 px-3 py-1 text-xs text-black dark:text-app-ink-dark"
                        >
                            {{ user.name }}
                            <button type="button" class="text-black/70 hover:text-black dark:text-app-muted-dark dark:hover:text-app-ink-dark" @click="removeInviteeUser(user.email)">
                                <TrashIcon class="h-3.5 w-3.5" />
                            </button>
                        </span>
                    </div>
                    <p v-if="!selectedInviteeUsers.length" class="text-xs text-black/70 dark:text-app-muted-dark">Nog geen genodigden geselecteerd.</p>
                </div>
                <label class="text-sm font-semibold text-black sm:pt-2.5 dark:text-app-ink-dark">URL</label>
                <div>
                    <input v-model="form.link_url" type="url" class="min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />
                    <p v-if="form.errors.link_url" class="mt-1 text-xs text-red-600">{{ form.errors.link_url }}</p>
                </div>
                <label class="text-sm font-semibold text-black sm:pt-2.5 dark:text-app-ink-dark">Bijlage</label>
                <div class="flex min-w-0 items-center gap-3">
                    <input ref="attachmentInput" type="file" class="hidden" @change="onAttachmentChange" />
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-app-border bg-white text-black shadow-sm transition hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15"
                        title="Bestand kiezen"
                        aria-label="Bestand kiezen"
                        @click="openAttachmentPicker"
                    >
                        <PaperClipIcon class="h-5 w-5" />
                    </button>
                    <span class="truncate text-sm text-black dark:text-app-ink-dark">{{ attachmentFileName }}</span>
                    <a
                        v-if="isEditing && props.item.attachment_name && !form.attachment_file"
                        :href="route('agenda.attachment.download', props.item.id)"
                        class="shrink-0 text-sm text-brand-blue underline"
                    >
                        Download
                    </a>
                    <button
                        v-if="form.attachment_file"
                        type="button"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-app-border bg-white text-black shadow-sm transition hover:bg-red-50 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:hover:bg-red-900/20"
                        title="Bijlage verwijderen"
                        aria-label="Bijlage verwijderen"
                        @click="clearAttachment"
                    >
                        <TrashIcon class="h-4 w-4" />
                    </button>
                </div>
                <label class="text-sm font-semibold text-black sm:pt-2.5 dark:text-app-ink-dark">Notities</label>
                <div>
                    <textarea v-model="form.notes" rows="3" class="min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />
                    <p v-if="form.errors.notes" class="mt-1 text-xs text-red-600">{{ form.errors.notes }}</p>
                </div>
                <template v-if="isBestuur">
                    <label class="text-sm font-semibold text-black sm:pt-2.5 dark:text-app-ink-dark">Zichtbaar voor</label>
                    <div class="space-y-2">
                        <select v-model="form.audience_scope" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:[&>option]:bg-app-canvas-dark dark:[&>option]:text-app-ink-dark">
                            <option value="self">Alleen mezelf</option>
                            <option value="all">Iedereen</option>
                            <option value="selected">Specifieke personen</option>
                        </select>
                        <div v-if="form.audience_scope === 'selected'" class="space-y-2">
                            <select class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:[&>option]:bg-app-canvas-dark dark:[&>option]:text-app-ink-dark" @change="addTargetUser">
                                <option value="">Kies een gebruiker...</option>
                                <option v-for="user in selectableTargetUsers" :key="`target-option-${user.id}`" :value="user.id">
                                    {{ user.name }} ({{ user.email }})
                                </option>
                            </select>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="user in selectedTargetUsers" :key="`target-chip-${user.id}`" class="inline-flex items-center gap-2 rounded-full bg-brand-blue/15 px-3 py-1 text-xs text-black dark:text-app-ink-dark">
                                    {{ user.name }}
                                    <button type="button" class="text-black/70 hover:text-black dark:text-app-muted-dark dark:hover:text-app-ink-dark" @click="removeTargetUser(user.id)">
                                        x
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
                <span class="hidden sm:block" />
                <button type="submit" class="btn-action-save" :disabled="form.processing" title="Opslaan" aria-label="Opslaan">
                    <DocumentCheckIcon class="h-5 w-5" />
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
