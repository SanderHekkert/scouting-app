<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useSaveRedirect } from '@/utils/saveForm';
import { ArrowUturnLeftIcon, DocumentCheckIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    event: { type: Object, required: true },
    leaders: { type: Array, default: () => [] },
    taskItems: { type: Array, default: () => [] },
    allSections: { type: Array, default: () => [] },
});
const { applySaveRedirect, saveFormOptions } = useSaveRedirect();

const page = usePage();
const activeSection = computed(() => page.props.auth?.active_section ?? 'dolfijnen');
const isBestuur = computed(() => activeSection.value === 'bestuur');
const isCreateMode = computed(() => !props.event?.id);
const sectionLabels = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const speltakLabel = computed(() => sectionLabels[activeSection.value] || 'Dolfijnen');
const shareableSections = computed(() =>
    (props.allSections || []).filter(
        (s) => s !== activeSection.value
            && s !== String(props.event?.section || '').trim()
            && !['bestuur', 'loodsen'].includes(s),
    ),
);

const form = useForm({
    theme: props.event.theme || '',
    event_date: String(props.event.event_date || '').slice(0, 10),
    event_type: props.event.event_type || '',
    activity: props.event.activity || '',
    program_by: props.event.program_by || '',
    location: props.event.location || '',
    time_slot: props.event.time_slot || '',
    invitees: props.event.invitees || '',
    link_url: props.event.link_url || '',
    attachments: props.event.attachments || '',
    attachment_file: null,
    absent: props.event.absent || '',
    notes: props.event.notes || '',
    task_item_ids: Array.isArray(props.event.task_item_ids) ? [...props.event.task_item_ids] : [],
    shared_sections: Array.isArray(props.event.shared_sections) ? [...props.event.shared_sections] : [],
});

function submit() {
    if (isCreateMode.value) {
        form
            .transform((data) => applySaveRedirect(data))
            .post(route('opkomsten.store'), saveFormOptions({
            forceFormData: true,
        }));
        return;
    }

    form
        .transform((data) => applySaveRedirect({ ...data, _method: 'patch' }))
        .post(route('opkomsten.update', props.event.id), saveFormOptions({
        forceFormData: true,
    }));
}

function onAttachmentChange(event) {
    form.attachment_file = event?.target?.files?.[0] || null;
}

function splitNames(value) {
    const text = String(value ?? '').trim();
    if (!text) return [];
    return [...new Set(text.split(',').map((n) => n.trim()).filter(Boolean))];
}

function joinNames(names) {
    return names.join(', ');
}

function firstNameOnly(name) {
    const s = String(name ?? '').trim();
    return s ? (s.split(/\s+/)[0] || s) : '';
}

function availableLeaders() {
    const selectedNames = splitNames(form.absent);
    const selectedFull = new Set(selectedNames.map((n) => n.toLowerCase()));
    const selectedFirst = new Set(selectedNames.map((n) => firstNameOnly(n).toLowerCase()).filter(Boolean));

    return (props.leaders || []).filter((leader) => {
        const full = String(leader).trim().toLowerCase();
        const first = firstNameOnly(leader).toLowerCase();
        return !selectedFull.has(full) && !selectedFirst.has(first);
    });
}

function addAbsentFromSelect(event) {
    const candidate = String(event?.target?.value || '').trim();
    if (!candidate) return;
    form.absent = joinNames([...new Set([...splitNames(form.absent), candidate])]);
    event.target.value = '';
}

function removeAbsentName(name) {
    form.absent = joinNames(splitNames(form.absent).filter((n) => n !== name));
}

function taskIds() {
    return [...new Set((Array.isArray(form.task_item_ids) ? form.task_item_ids : []).map((id) => Number(id)).filter(Number.isFinite))];
}

function taskLabelById(id) {
    return props.taskItems.find((task) => Number(task.id) === Number(id))?.title || `Taak #${id}`;
}

function availableTasks() {
    const selected = new Set(taskIds());
    return (props.taskItems || []).filter((task) => !selected.has(Number(task.id)));
}

function addTaskFromSelect(event) {
    const id = Number(event?.target?.value);
    if (!Number.isFinite(id)) return;
    form.task_item_ids = [...new Set([...taskIds(), id])];
    event.target.value = '';
}

function removeTask(taskId) {
    form.task_item_ids = taskIds().filter((id) => id !== Number(taskId));
}
</script>

<template>
    <Head :title="`${speltakLabel} - ${isCreateMode ? 'Opkomst toevoegen' : 'Opkomst bewerken'}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - {{ isCreateMode ? 'Opkomst toevoegen' : 'Opkomst bewerken' }}</h2>
                <Link :href="route('opkomsten.index')" class="btn-action-back" title="Terug" aria-label="Terug">
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 text-app-ink shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark dark:text-app-ink-dark" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-[9rem_1fr] sm:items-start">
                <label v-if="!isBestuur" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Thema</label>
                <input v-if="!isBestuur" v-model="form.theme" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Datum</label>
                <input v-model="form.event_date" type="date" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <label v-if="!isBestuur" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Type opkomst</label>
                <input v-if="!isBestuur" v-model="form.event_type" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">{{ isBestuur ? 'Naam activiteit' : 'Wat ga je doen?' }}</label>
                <input v-if="isBestuur" v-model="form.theme" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />
                <input v-else v-model="form.activity" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <template v-if="isBestuur">
                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Locatie</label>
                    <input v-model="form.location" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Tijdstip</label>
                    <input v-model="form.time_slot" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Genodigden</label>
                    <textarea v-model="form.invitees" rows="2" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">URL</label>
                    <input v-model="form.link_url" type="url" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Bijlagen</label>
                    <div class="space-y-2">
                        <a
                            v-if="props.event.attachment_name"
                            :href="route('opkomsten.attachment.download', props.event.id)"
                            class="inline-flex text-sm text-brand-blue underline"
                        >
                            {{ props.event.attachment_name }}
                        </a>
                        <input type="file" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" @change="onAttachmentChange" />
                    </div>
                </template>

                <label v-if="!isBestuur" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Programma door</label>
                <select v-if="!isBestuur" v-model="form.program_by" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark">
                    <option value="">Geen gekozen</option>
                    <option v-for="leader in leaders" :key="`leader-${leader}`" :value="leader">{{ leader }}</option>
                </select>

                <label v-if="!isBestuur" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Afwezig</label>
                <div v-if="!isBestuur">
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="name in splitNames(form.absent)"
                            :key="`absent-chip-${name}`"
                            class="inline-flex items-center gap-1 rounded-full bg-red-500/15 px-2 py-0.5 text-xs text-red-800 dark:text-red-200"
                        >
                            {{ firstNameOnly(name) }}
                            <button type="button" class="rounded p-0.5 hover:bg-brand-blue/25" @click="removeAbsentName(name)">
                                <XMarkIcon class="h-3.5 w-3.5" />
                            </button>
                        </span>
                    </div>
                    <select class="mt-2 min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" @change="addAbsentFromSelect($event)">
                        <option value="">Naam toevoegen...</option>
                        <option v-for="leader in availableLeaders()" :key="`leader-add-${leader}`" :value="leader">
                            {{ firstNameOnly(leader) }}
                        </option>
                    </select>
                </div>

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Taken</label>
                <div>
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="id in taskIds()"
                            :key="`task-chip-${id}`"
                            class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs text-app-ink dark:text-app-ink-dark"
                        >
                            {{ taskLabelById(id) }}
                            <button type="button" class="rounded p-0.5 hover:bg-brand-blue/25" @click="removeTask(id)">
                                <XMarkIcon class="h-3.5 w-3.5" />
                            </button>
                        </span>
                    </div>
                    <select class="mt-2 min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" @change="addTaskFromSelect($event)">
                        <option value="">Taak toevoegen...</option>
                        <option v-for="task in availableTasks()" :key="`task-opt-${task.id}`" :value="task.id">
                            {{ task.title }}
                        </option>
                    </select>
                </div>

                <label v-if="!isBestuur" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Gezamenlijk met</label>
                <div v-if="!isBestuur" class="flex flex-wrap gap-2">
                    <label
                        v-for="section in shareableSections"
                        :key="`share-${section}`"
                        class="inline-flex items-center gap-2 rounded border border-app-border bg-white px-2 py-1 text-xs text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    >
                        <input v-model="form.shared_sections" type="checkbox" :value="section" class="rounded border-app-border" />
                        {{ sectionLabels[section] || section }}
                    </label>
                </div>

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Bijzonderheden</label>
                <textarea v-model="form.notes" rows="4" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <span class="hidden sm:block" aria-hidden="true" />
                <div>
                    <button type="submit" class="btn-action-save" :disabled="form.processing" title="Opslaan" aria-label="Opslaan">
                        <DocumentCheckIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
